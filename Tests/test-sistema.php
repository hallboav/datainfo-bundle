<?php

use Hallboav\DatainfoBundle\Sistema\Client\Middleware\JsonResponse;
use Hallboav\DatainfoBundle\Sistema\Effort\EffortType;
use Hallboav\DatainfoBundle\Sistema\Effort\FilteringEffortType;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Hallboav\DatainfoBundle\Sistema\Sistema;
use Hallboav\DatainfoBundle\Sistema\Task\Task;
use Hallboav\DatainfoBundle\Sistema\Task\TaskCollection;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\HttpCache\Store;

require dirname(__DIR__) . '/vendor/autoload.php';

$client = HttpClient::create([
    'base_uri' => 'http://sistema.datainfo.inf.br',
    'timeout' => 30,
]);

$store = new Store('/tmp/sf-cache/');
$client = CachingHttpClient($client, $store, [
    'default_ttl' => 15 * 60,
    'private_headers' => [],
]);

$user = new class implements DatainfoUserInterface {
    public function getDatainfoUsername(): string
    {
        return 'data52360';
    }

    public function getDatainfoPassword(): string
    {
        return 'Datainfo1119';
    }

    public function getPis(): string
    {
        return '';
    }
};

$tz = new \DateTimeZone('America/Sao_Paulo');
$sistema = new Sistema($client, new EventDispatcher(), new FilesystemAdapter());

$balance = $sistema->getBalance($user, new \DateTime('2019-11-01', $tz), new \DateTime('2019-11-30', $tz));

echo json_encode($balance), PHP_EOL;
echo 'A trabalhar: ', $balance->getTimeToWork(), PHP_EOL;
echo 'Trabalhadas: ', $balance->getWorkedTime(), PHP_EOL;

echo '========================================', PHP_EOL;

$projects = $sistema->getProjects($user);
foreach ($projects as $project) {
    echo json_encode($project), PHP_EOL;
}

echo '========================================', PHP_EOL;

foreach ($projects as $project) {
    $activities = $sistema->getActivities($user, $project);
    foreach ($activities as $activity) {
        echo json_encode($activity), PHP_EOL;
    }
}

echo '========================================', PHP_EOL;

$filteringEfforType = new FilteringEffortType('todos');
$report = $sistema->getWidgetReport($user, new \DateTime('2019-11-01', $tz), new \DateTime('2019-11-30', $tz), $filteringEfforType);

echo json_encode($report), PHP_EOL;

echo '========================================', PHP_EOL;

$tasks = new TaskCollection([
    new Task(
        (new \DateTime('today', $tz))->setTime(0, 0),
        new \DateTime('now', $tz),
        new \DateTime('1 hour', $tz),
        'Nuxa...',
        'NXA-0001'
    ),
    new Task(
        (new \DateTime('today', $tz))->setTime(0, 0),
        new \DateTime('1 hour 5 seconds', $tz),
        new \DateTime('2 hours', $tz),
        'Nuxa...',
        'NXA-0002'
    ),
]);

$project = $projects->getIterator()->offsetGet(0);
$activities = $sistema->getActivities($user, $project);
$activity = $activities->getIterator()->offsetGet(0);
$effortType = new EffortType('normal');

$messages = $sistema->launchPerformedTasks($user, $activity, $tasks, $effortType);
print_r($messages);

// Loga no service
// Entra na página de lançar pontos
// Clica no dia (data) (terá um request com a tabela contendo o P100_NUMSEQESFORCO)
// Após obter o P100_NUMSEQESFORCO, deve-se fazer um request para excluir

// $message = $sistema->deleteTask($user, $taskId);
// print_r($message);
