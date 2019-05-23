<?php

namespace Hallboav\DatainfoBundle\Sistema;

use GuzzleHttp\Client;

use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\SetCookie;
use Hallboav\DatainfoBundle\Event\DatainfoEvents;
use Hallboav\DatainfoBundle\Sistema\Apex\Launcher;
use Hallboav\DatainfoBundle\Sistema\Balance\Balance;
use Hallboav\DatainfoBundle\Sistema\Activity\Project;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Hallboav\DatainfoBundle\Event\AuthenticationEvent;
use Hallboav\DatainfoBundle\Sistema\Activity\Activity;
use Hallboav\DatainfoBundle\Sistema\Effort\EffortType;
use Hallboav\DatainfoBundle\Sistema\Apex\Authenticator;
use Hallboav\DatainfoBundle\Sistema\Apex\ActivityLoader;
use Hallboav\DatainfoBundle\Sistema\Apex\BalanceChecker;
use Hallboav\DatainfoBundle\Sistema\Apex\WidgetReporter;
use Hallboav\DatainfoBundle\Sistema\Crawler\LoginPageCrawler;
use Hallboav\DatainfoBundle\Sistema\Crawler\QueryPageCrawler;
use Hallboav\DatainfoBundle\Sistema\Effort\FilteringEffortType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Hallboav\DatainfoBundle\Sistema\Crawler\LauncherPageCrawler;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Sistema
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
     * @param int                      $loginCookieLifetime
     * @param LoggerInterface          $logger
     */
    public function __construct(ClientInterface $client, EventDispatcherInterface $dispatcher, AdapterInterface $cache, int $loginCookieLifetime, LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->dispatcher = $dispatcher;
        $this->cache = $cache;
        $this->loginCookieLifetime = $loginCookieLifetime;
        $this->logger = $logger;
    }

    /**
     * Obtém os projetos.
     *
     * @param DatainfoUserInterface $user
     *
     * @return array
     */
    public function getProjects(DatainfoUserInterface $user): array
    {
        $this->authenticate($user);

        if (null !== $this->logger) {
            $this->logger->info('Buscando projetos no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.project.%s', $user->getDatainfoUsername());
        $launcherPageCrawler = new LauncherPageCrawler($this->client, $this->cache, $cacheKey, $this->loginCookieLifetime);
        $contents = $launcherPageCrawler->crawl($this->instance);

        return $launcherPageCrawler->getProjects($contents);
    }

    /**
     * Obtém as atividades.
     *
     * @param DatainfoUserInterface $user
     * @param Project               $project
     *
     * @return array
     */
    public function getActivities(DatainfoUserInterface $user, Project $project): array
    {
        $this->authenticate($user);

        if (null !== $this->logger) {
            $this->logger->info('Buscando atividades do projeto no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'project_id' => $project->getId(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.activity.%s', $user->getDatainfoUsername());
        $launcherPageCrawler = new LauncherPageCrawler($this->client, $this->cache, $cacheKey, $this->loginCookieLifetime);
        $contents = $launcherPageCrawler->crawl($this->instance);
        $ajaxId = $launcherPageCrawler->getAjaxIdForActivitiesFetching($contents);

        $activityLoader = new ActivityLoader($this->client);

        return $activityLoader->load($this->instance, $ajaxId, $project);
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
        $queryPageCrawler = new QueryPageCrawler($this->client, $this->cache, $cacheKey, $this->loginCookieLifetime);
        $contents = $queryPageCrawler->crawl($this->instance);
        $ajaxId = $queryPageCrawler->getAjaxIdForBalanceChecking($contents);

        $balanceChecker = new BalanceChecker($this->client);

        return $balanceChecker->check($this->instance, $ajaxId, $startDate, $endDate);
    }

    /**
     * Obtém o relatório no intervalo especificado.
     *
     * @param DatainfoUserInterface $user
     * @param \DateTimeInterface    $startDate
     * @param \DateTimeInterface    $endDate
     * @param string                $effortType "todos" é o valor padrão.
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

        $widgetReporter = new WidgetReporter($this->client);

        return $widgetReporter->report($this->instance, $user, $startDate, $endDate, $effortType);
    }

    /**
     * Lança várias tarefas.
     *
     * @param DatainfoUserInterface $user
     * @param Activity              $activity   Atividade executada nas tarefas.
     * @param array                 $tasks      Uma coleção de tarefas, cada tarefa contém data, hora inicial,
     *                                          hora final, ticket e descrição.
     * @param EffortType            $effortType Tipo de esforço.
     *
     * @return array Mensagens com resultado de cada lançamento.
     */
    public function launchPerformedTasks(DatainfoUserInterface $user, Activity $activity, array $tasks, EffortType $effortType): array
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
        $launcherPageCrawler = new LauncherPageCrawler($this->client, $this->cache, $cacheKey, $this->loginCookieLifetime);
        $contents = $launcherPageCrawler->crawl($this->instance);
        $ajaxId = $launcherPageCrawler->getAjaxIdForLaunching($contents);

        $launcher = new Launcher($this->client);

        $messages = [];
        foreach ($tasks as $task) {
            $messages[] = [
                'date' => $task->getDate()->format(\DateTime::ATOM),
                'start_time' => $task->getStartTime()->format(\DateTime::ATOM),
                'end_time' => $task->getEndTime()->format(\DateTime::ATOM),
                'message' => $launcher->launch($user, $this->instance, $ajaxId, $task, $activity, $effortType),
            ];
        }

        return $messages;
    }

    public function deleteTask(DatainfoUserInterface $user)
    {
        $this->authenticate($user);

        $cacheKey = 'foo';
        $launcherPageCrawler = new LauncherPageCrawler($this->client, $this->cache, $cacheKey, $this->loginCookieLifetime);
        $contents = $launcherPageCrawler->crawl($this->instance);
        $ajaxId = $launcherPageCrawler->getAjaxIdForFoo($contents);



        // $user, string $instance, string $ajaxId, string $performedTaskId, Task $task
    }

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
        $userCredentials = $this->cache->getItem($cacheKey);

        if ($userCredentials->isHit()) {
            // Carregando credenciais do cache
            $userCredentialsAsArray = $userCredentials->get();
            $this->instance = $userCredentialsAsArray['instance'];
            $cookies = $userCredentialsAsArray['cookies'];

            foreach ($cookies as $cookie) {
                $this->client->getConfig('cookies')
                    ->setCookie(SetCookie::fromString($cookie));
            }

            return;
        }

        $cacheKey = sprintf('cache.crawler.login.%s', $user->getDatainfoUsername());
        $loginPageCrawler = new LoginPageCrawler($this->client, $this->cache, $cacheKey, $this->loginCookieLifetime);
        $this->instance = $loginPageCrawler->getInstance($contents);
        $salt = $loginPageCrawler->getSalt();
        $protected = $loginPageCrawler->getProtected();

        $authenticator = new Authenticator($this->client);
        $authenticator->authenticate($user, $this->instance, $salt, $protected);

        // Lendo os cookies no client
        $jar = $this->client->getConfig('cookies');
        $cookies = [
            'LOGIN_USERNAME_COOKIE' => (string) $jar->getCookieByName('LOGIN_USERNAME_COOKIE'),
            'ORA_WWV_APP_104' => (string) $jar->getCookieByName('ORA_WWV_APP_104'),
        ];

        $userCredentials->set([
            'instance' => $this->instance,
            'cookies' => $cookies,
        ]);

        // Salvando os cookies (que expiram em 1 hora) no cache
        $userCredentials->expiresAfter($this->loginCookieLifetime);
        $this->cache->save($userCredentials);

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
}
