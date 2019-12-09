<?php

namespace Hallboav\DatainfoBundle\Sistema;

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
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
final class Sistema
{
    /**
     * @var HttpClientInterface
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
     * @param HttpClientInterface      $client
     * @param EventDispatcherInterface $dispatcher
     * @param AdapterInterface         $cache
     * @param LoggerInterface          $logger
     */
    public function __construct(HttpClientInterface $client, EventDispatcherInterface $dispatcher, AdapterInterface $cache, LoggerInterface $logger = null)
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
        $credentials = $this->authenticate($user);
        $instance = $credentials['p_instance'];
        $parsedOraWwvApp104Cookie = $credentials['parsed_ora_wwv_app_104_cookie'];

        if (null !== $this->logger) {
            $this->logger->info('Buscando projetos no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.project.%s', $user->getDatainfoUsername());
        $launcherPageCrawler = new LauncherPageCrawler($this->client, $instance, $parsedOraWwvApp104Cookie);

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
        $credentials = $this->authenticate($user);
        $instance = $credentials['p_instance'];
        $parsedOraWwvApp104Cookie = $credentials['parsed_ora_wwv_app_104_cookie'];

        if (null !== $this->logger) {
            $this->logger->info('Buscando atividades do projeto no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'project_id' => $project->getId(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.activity.%s', $user->getDatainfoUsername());
        $launcherPageCrawler = new LauncherPageCrawler($this->client, $instance, $parsedOraWwvApp104Cookie);

        $activityLoader = new ActivityLoader($this->client);

        return $activityLoader->load(
            $project,
            $instance,
            $launcherPageCrawler->getAjaxIdForActivitiesFetching(),
            $launcherPageCrawler->getSalt(),
            $launcherPageCrawler->getProtected(),
            $parsedOraWwvApp104Cookie
        );
    }

    public function getQueryPageData(DatainfoUserInterface $user): array
    {
        $cacheKey = sprintf('cache.crawler.query_page.%s', $user->getDatainfoUsername());
        $queryPageCacheItem = $this->cache->getItem($cacheKey);

        if ($queryPageCacheItem->isHit()) {
            return $queryPageCacheItem->get();
        }

        $credentials = $this->authenticate($user);
        $instance = $credentials['p_instance'];
        $parsedOraWwvApp104Cookie = $credentials['parsed_ora_wwv_app_104_cookie'];

        $queryPageCrawler = new QueryPageCrawler($this->client, $instance, $parsedOraWwvApp104Cookie);
        $queryPageData = [
            'p_instance'                    => $instance,
            'parsed_ora_wwv_app_104_cookie' => $parsedOraWwvApp104Cookie,
            'ajax_id_for_balance_checking'  => $queryPageCrawler->getAjaxIdForBalanceChecking(),
            'ajax_id_for_reporting'         => $queryPageCrawler->getAjaxIdForReporting(),
            'salt'                          => $queryPageCrawler->getSalt(),
            'protected'                     => $queryPageCrawler->getProtected(),
        ];

        $queryPageCacheItem->set($queryPageData);
        $this->cache->save($queryPageCacheItem);

        return $queryPageData;
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
        if (null !== $this->logger) {
            $this->logger->info('Buscando saldo de horas no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
            ]);
        }

        $queryPageData = $this->getQueryPageData($user);
        $balanceChecker = new BalanceChecker($this->client);

        return $balanceChecker->check(
            $startDate,
            $endDate,
            $queryPageData['p_instance'],
            $queryPageData['ajax_id_for_balance_checking'],
            $queryPageData['salt'],
            $queryPageData['protected'],
            $queryPageData['parsed_ora_wwv_app_104_cookie']
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
        if (null !== $this->logger) {
            $this->logger->info('Buscando relatório no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'effort_type_id' => $effortType->getId(),
            ]);
        }

        $queryPageData = $this->getQueryPageData($user);
        $widgetReporter = new WidgetReporter($this->client);

        return $widgetReporter->report(
            $user,
            $startDate,
            $endDate,
            $effortType,
            $queryPageData['p_instance'],
            $queryPageData['ajax_id_for_reporting'],
            $queryPageData['salt'],
            $queryPageData['protected'],
            $queryPageData['parsed_ora_wwv_app_104_cookie']
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
        $credentials = $this->authenticate($user);
        $instance = $credentials['p_instance'];
        $parsedOraWwvApp104Cookie = $credentials['parsed_ora_wwv_app_104_cookie'];

        if (null !== $this->logger) {
            $this->logger->info('Lançando um ou mais registros de pontos no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
                'tasks' => print_r($tasks, true),
                'project_id' => $activity->getProject()->getId(),
                'activity_id' => $activity->getId(),
            ]);
        }

        $cacheKey = sprintf('cache.crawler.launcher.%s', $user->getDatainfoUsername());
        $launcherPageCrawler = new LauncherPageCrawler($this->client, $instance, $parsedOraWwvApp104Cookie);

        $launcher = new Launcher($this->client);
        $messages = $launcher->launch(
            $user,
            $tasks,
            $activity,
            $effortType,
            $instance,
            $launcherPageCrawler->getAjaxIdForLaunching(),
            $launcherPageCrawler->getSalt(),
            $launcherPageCrawler->getProtected(),
            $parsedOraWwvApp104Cookie
        );

        return $messages;
    }

    /**
     * Autentica o usuário.
     *
     * @param DatainfoUserInterface $user
     *
     * @return array
     */
    protected function authenticate(DatainfoUserInterface $user): array
    {
        $cacheKey = sprintf('cache.credentials.%s', $user->getDatainfoUsername());
        $credentialsCacheItem = $this->cache->getItem($cacheKey);
        if ($credentialsCacheItem->isHit()) {
            return $credentialsCacheItem->get();
        }

        $loginPageCrawler = new LoginPageCrawler($this->client, 'not_used');
        $instance = $loginPageCrawler->getInstance();
        $parsedOraWwvApp104Cookie = $loginPageCrawler->getLastParsedOraWwvApp104Cookie();

        $authenticator = new Authenticator($this->client);
        $parsedOraWwvApp104CookieUpdated = $authenticator->authenticate(
            $user,
            $instance,
            $loginPageCrawler->getSalt(),
            $loginPageCrawler->getProtected(),
            $parsedOraWwvApp104Cookie,
        );

        $credentials = [
            'p_instance' => $instance,
            'parsed_ora_wwv_app_104_cookie' => $parsedOraWwvApp104CookieUpdated,
        ];

        // Salvando os cookies no cache
        $credentialsCacheItem->set($credentials);
        $this->cache->save($credentialsCacheItem);

        // Disparando evento de autenticação
        $event = new AuthenticationEvent($user);
        $this->dispatcher->dispatch($event, DatainfoEvents::AUTHENTICATION_SUCCESS);

        if (null !== $this->logger) {
            $this->logger->info('Usuário autenticado no Service.', [
                'datainfo_username' => $user->getDatainfoUsername(),
            ]);
        }

        return $credentials;
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
            sprintf('cache.crawler.activity.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.query_page.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.launcher.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.project.%s', $user->getDatainfoUsername()),
            // sprintf('cache.crawler.task_delete.%s', $user->getDatainfoUsername()),
            sprintf('cache.crawler.widget_report.%s', $user->getDatainfoUsername()),
            sprintf('cache.credentials.%s', $user->getDatainfoUsername()),
        ];

        foreach ($items as $item) {
            $this->cache->deleteItem($item);
        }
    }

    // public function deleteTask(DatainfoUserInterface $user): string
    // {
    //     $this->authenticate($user);

    //     $cacheKey = sprintf('cache.crawler.task_delete.%s', $user->getDatainfoUsername());
    //     $launcherPageCrawler = new LauncherPageCrawler(
    //         $this->client,
    //         $instance,
    //         $this->cache,
    //         $cacheKey
    //     );

    //     $taskDeleter = new TaskDeleter($this->client);

    //     return $taskDeleter->deleteTask(
    //         $user,
    //         $task,
    //         $performedTaskId,
    //         $instance,
    //         $launcherPageCrawler->getAjaxIdForTaskDeleting(),
    //         $launcherPageCrawler->getSalt(),
    //         $launcherPageCrawler->getProtected()
    //     );
    // }
}
