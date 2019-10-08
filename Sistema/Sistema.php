<?php

namespace Hallboav\DatainfoBundle\Sistema;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\SetCookie;
use Hallboav\DatainfoBundle\Event\AuthenticationEvent;
use Hallboav\DatainfoBundle\Event\DatainfoEvents;
use Hallboav\DatainfoBundle\Sistema\Activity\Activity;
use Hallboav\DatainfoBundle\Sistema\Activity\ActivityCollection;
use Hallboav\DatainfoBundle\Sistema\Activity\Project;
use Hallboav\DatainfoBundle\Sistema\Activity\ProjectCollection;
use Hallboav\DatainfoBundle\Sistema\Apex\ActivityLoader;
use Hallboav\DatainfoBundle\Sistema\Apex\Authenticator;
use Hallboav\DatainfoBundle\Sistema\Apex\BalanceChecker;
use Hallboav\DatainfoBundle\Sistema\Apex\Launcher;
use Hallboav\DatainfoBundle\Sistema\Apex\WidgetReporter;
use Hallboav\DatainfoBundle\Sistema\Balance\Balance;
use Hallboav\DatainfoBundle\Sistema\Crawler\LauncherPageCrawler;
use Hallboav\DatainfoBundle\Sistema\Crawler\LoginPageCrawler;
use Hallboav\DatainfoBundle\Sistema\Crawler\QueryPageCrawler;
use Hallboav\DatainfoBundle\Sistema\Effort\EffortType;
use Hallboav\DatainfoBundle\Sistema\Effort\FilteringEffortType;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Hallboav\DatainfoBundle\Sistema\Task\TaskCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
final class Sistema
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var AdapterInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $instance;

    /**
     * Construtor.
     *
     * @param ClientInterface          $client
     * @param EventDispatcherInterface $dispatcher
     * @param AdapterInterface         $cache
     * @param LoggerInterface          $logger
     */
    public function __construct(ClientInterface $client, EventDispatcherInterface $dispatcher, AdapterInterface $cache, LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->dispatcher = $dispatcher;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Obtém os projetos.
     *
     * @param DatainfoUserInterface $user
     *
     * @return ProjectCollection
     */
    public function getProjects(DatainfoUserInterface $user): ProjectCollection
    {
        $this->authenticate($user);

        if (null !== $this->logger) {
            $this->logger->info('Buscando projetos no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.project.%s', $user->getDatainfoUsername());
        $launcherPageCrawler = new LauncherPageCrawler(
            $this->client,
            $this->instance,
            $this->cache,
            $cacheKey
        );

        return $launcherPageCrawler->getProjects();
    }

    /**
     * Obtém as atividades.
     *
     * @param DatainfoUserInterface $user
     * @param Project               $project
     *
     * @return ActivityCollection
     */
    public function getActivities(DatainfoUserInterface $user, Project $project): ActivityCollection
    {
        $this->authenticate($user);

        if (null !== $this->logger) {
            $this->logger->info('Buscando atividades do projeto no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'project_id' => $project->getId(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.activity.%s', $user->getDatainfoUsername());
        $launcherPageCrawler = new LauncherPageCrawler(
            $this->client,
            $this->instance,
            $this->cache,
            $cacheKey
        );

        $activityLoader = new ActivityLoader($this->client);

        return $activityLoader->load(
            $project,
            $this->instance,
            $launcherPageCrawler->getAjaxIdForActivitiesFetching(),
            $launcherPageCrawler->getSalt(),
            $launcherPageCrawler->getProtected()
        );
    }

    /**
     * Obtém o saldo no intervalo especificado.
     *
     * @param DatainfoUserInterface $user
     * @param \DateTimeInterface    $startDate
     * @param \DateTimeInterface    $endDate
     *
     * @return Balance|null Instância de Balance contendo horas trabalhadas e horas a trabalhar ou nulo caso nenhum saldo seja encontrado.
     */
    public function getBalance(DatainfoUserInterface $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): ?Balance
    {
        $this->authenticate($user);

        if (null !== $this->logger) {
            $this->logger->info('Buscando saldo de horas no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.balance.%s', $user->getDatainfoUsername());
        $queryPageCrawler = new QueryPageCrawler(
            $this->client,
            $this->instance,
            $this->cache,
            $cacheKey
        );

        $balanceChecker = new BalanceChecker($this->client);

        return $balanceChecker->check(
            $startDate,
            $endDate,
            $this->instance,
            $queryPageCrawler->getAjaxIdForBalanceChecking(),
            $queryPageCrawler->getSalt(),
            $queryPageCrawler->getProtected()
        );
    }

    /**
     * Obtém o relatório no intervalo especificado.
     *
     * @param DatainfoUserInterface $user
     * @param \DateTimeInterface    $startDate
     * @param \DateTimeInterface    $endDate
     * @param string                $effortType
     *
     * @return array Relatório.
     */
    public function getWidgetReport(DatainfoUserInterface $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate, FilteringEffortType $effortType): array
    {
        $this->authenticate($user);

        if (null !== $this->logger) {
            $this->logger->info('Buscando relatório no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'effort_type_id' => $effortType->getId(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.widget_report.%s', $user->getDatainfoUsername());
        $queryPageCrawler = new QueryPageCrawler(
            $this->client,
            $this->instance,
            $this->cache,
            $cacheKey
        );

        $widgetReporter = new WidgetReporter($this->client);

        return $widgetReporter->report(
            $user,
            $startDate,
            $endDate,
            $effortType,
            $this->instance,
            $queryPageCrawler->getAjaxIdForReporting(),
            $queryPageCrawler->getSalt(),
            $queryPageCrawler->getProtected()
        );
    }

    /**
     * Lança várias tarefas.
     *
     * @param DatainfoUserInterface $user
     * @param Activity              $activity   Atividade executada nas tarefas.
     * @param TaskCollection        $tasks      Uma coleção de tarefas, cada tarefa contém data, hora inicial,
     *                                          hora final, ticket e descrição.
     * @param EffortType            $effortType Tipo de esforço.
     *
     * @return array Mensagens com resultado de cada lançamento.
     */
    public function launchPerformedTasks(DatainfoUserInterface $user, Activity $activity, TaskCollection $tasks, EffortType $effortType): array
    {
        $this->authenticate($user);

        if (null !== $this->logger) {
            $this->logger->info('Lançando um ou mais registros de pontos no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'tasks' => print_r($tasks, true),
                'project_id' => $activity->getProject()->getId(),
                'activity_id' => $activity->getId(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.launcher.%s', $user->getDatainfoUsername());
        $launcherPageCrawler = new LauncherPageCrawler(
            $this->client,
            $this->instance,
            $this->cache,
            $cacheKey
        );

        $launcher = new Launcher($this->client);

        $messages = [];
        foreach ($tasks as $task) {
            $message = $launcher->launch(
                $user,
                $task,
                $activity,
                $effortType,
                $this->instance,
                $launcherPageCrawler->getAjaxIdForLaunching(),
                $launcherPageCrawler->getSalt(),
                $launcherPageCrawler->getProtected()
            );

            $messages[] = [
                'date' => $task->getDate()->format(\DateTime::ATOM),
                'start_time' => $task->getStartTime()->format(\DateTime::ATOM),
                'end_time' => $task->getEndTime()->format(\DateTime::ATOM),
                'message' => $message,
            ];
        }

        return $messages;
    }

    // public function deleteTask(DatainfoUserInterface $user): string
    // {
    //     $this->authenticate($user);

    //     $cacheKey = sprintf('cache.crawler.task_delete.%s', $user->getDatainfoUsername());
    //     $launcherPageCrawler = new LauncherPageCrawler(
    //         $this->client,
    //         $this->instance,
    //         $this->cache,
    //         $cacheKey
    //     );

    //     $taskDeleter = new TaskDeleter($this->client);

    //     return $taskDeleter->deleteTask(
    //         $user,
    //         $task,
    //         $performedTaskId,
    //         $this->instance,
    //         $launcherPageCrawler->getAjaxIdForTaskDeleting(),
    //         $launcherPageCrawler->getSalt(),
    //         $launcherPageCrawler->getProtected()
    //     );
    // }

    /**
     * Autentica o usuário.
     *
     * @param DatainfoUserInterface $user
     *
     * @return void
     */
    protected function authenticate(DatainfoUserInterface $user): void
    {
        $cacheKey = sprintf('cache.credentials.%s', $user->getDatainfoUsername());
        $userCredentialsCacheItem = $this->cache->getItem($cacheKey);

        if ($userCredentialsCacheItem->isHit()) {
            $userCredentials = $userCredentialsCacheItem->get();
            $this->instance = $userCredentials['instance'];
            $cookies = $userCredentials['cookies'];

            foreach ($cookies as $cookie) {
                $this->client->getConfig('cookies')
                    ->setCookie(SetCookie::fromString($cookie));
            }

            return;
        }

        $cacheKey = sprintf('cache.crawler.login.%s', $user->getDatainfoUsername());
        $loginPageCrawler = new LoginPageCrawler(
            $this->client,
            '',
            $this->cache,
            $cacheKey
        );
        $this->instance = $loginPageCrawler->getInstance();

        $authenticator = new Authenticator($this->client);
        $authenticator->authenticate(
            $user,
            $this->instance,
            $loginPageCrawler->getSalt(),
            $loginPageCrawler->getProtected()
        );

        // Lendo os cookies no client
        $jar = $this->client->getConfig('cookies');
        $cookies = [
            'LOGIN_USERNAME_COOKIE' => (string) $jar->getCookieByName('LOGIN_USERNAME_COOKIE'),
            'ORA_WWV_APP_104' => (string) $jar->getCookieByName('ORA_WWV_APP_104'),
        ];

        $userCredentialsCacheItem->set([
            'instance' => $this->instance,
            'cookies' => $cookies,
        ]);

        // Salvando os cookies no cache
        $this->cache->save($userCredentialsCacheItem);

        // Disparando evento de autenticação
        $event = new AuthenticationEvent($user);
        $this->dispatcher->dispatch(DatainfoEvents::AUTHENTICATION_SUCCESS, $event);

        // Logging
        if (null !== $this->logger) {
            $this->logger->info('Usuário autenticado no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
            ]);
        }
    }

    /**
     * Remove o cache de autenticação de um usuário específico.
     *
     * @param DatainfoUserInterface $user
     *
     * @return void
     */
    public function invalidateUserCache(DatainfoUserInterface $user): void
    {
        $items = [
            sprintf('cache.crawler.project.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.activity.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.balance.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.widget_report.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.launcher.%s', $user->getDatainfoUsername()),
            // sprintf('cache.crawler.task_delete.%s', $user->getDatainfoUsername()),
            sprintf('cache.credentials.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.login.%s', $user->getDatainfoUsername()),
        ];

        foreach ($items as $item) {
            $this->cache->deleteItem($item);
        }
    }
}
