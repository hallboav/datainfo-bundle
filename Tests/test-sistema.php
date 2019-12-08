<?php

use Symfony\Component\HttpClient\HttpClient;
use Hallboav\DatainfoBundle\Sistema\Client\Middleware\JsonResponse;
use Hallboav\DatainfoBundle\Sistema\Effort\EffortType;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Hallboav\DatainfoBundle\Sistema\Sistema;
use Hallboav\DatainfoBundle\Sistema\Task\Task;
use Hallboav\DatainfoBundle\Sistema\Task\TaskCollection;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;

require dirname(__DIR__) . '/vendor/autoload.php';

$client = HttpClient::create([
    'base_uri' => 'http://sistema.datainfo.inf.br',
    'timeout' => 30,
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

$balance = $sistema->getBalance($user, new \DateTime('2019-04-01', $tz), new \DateTime('2019-04-30', $tz));

echo json_encode($balance);

echo 'A trabalhar: ', $balance->getTimeToWork(), PHP_EOL;
echo 'Trabalhadas: ', $balance->getWorkedTime(), PHP_EOL;

echo '========================================', PHP_EOL;

$projects = $sistema->getProjects($user);
foreach ($projects as $project) {
    $activities = $sistema->getActivities($user, $project);
    foreach ($activities as $activity) {
        echo json_encode($activity), PHP_EOL;
    }
}

// echo '========================================', PHP_EOL;

// $tasks = new TaskCollection([
//     new Task(
//         (new \DateTime('today', $tz))->setTime(0, 0),
//         new \DateTime('now', $tz),
//         new \DateTime('1 hour', $tz),
//         'Nuxa...',
//         'NXA-0001'
//     ),
// ]);

// $project = $projects->getIterator()->offsetGet(0);
// $activities = $sistema->getActivities($user, $project);
// $activity = $activities->getIterator()->offsetGet(0);
// $effortType = new EffortType('normal');

// $messages = $sistema->launchPerformedTasks($user, $activity, $tasks, $effortType);
// print_r($messages);

// Loga no service
// Entra na página de lançar pontos
// Clica no dia (data) (terá um request com a tabela contendo o P100_NUMSEQESFORCO)
// Após obter o P100_NUMSEQESFORCO, deve-se fazer um request para excluir

// $message = $sistema->deleteTask($user, $taskId);
// print_r($message);
