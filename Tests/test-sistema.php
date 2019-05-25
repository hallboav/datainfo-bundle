<?php

use GuzzleHttp\Client;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Hallboav\DatainfoBundle\Sistema\Sistema;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Hallboav\DatainfoBundle\Sistema\Client\Middleware\JsonResponse;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

require dirname(__DIR__) . '/vendor/autoload.php';

$handlerStack = HandlerStack::create();
$handlerStack->push(Middleware::mapResponse('\Hallboav\DatainfoBundle\Sistema\Client\Middleware\JsonResponse::parse'));

$client = new Client([
    'cookies' => true,
    'base_uri' => 'http://sistema.datainfo.inf.br',
    'connect_timeout' => 30,
    'handler' => $handlerStack,
]);

$sistema = new Sistema($client, new EventDispatcher(), new FilesystemAdapter(), 30);

$user = new class implements DatainfoUserInterface {
    public function getDatainfoUsername(): string
    {
        return 'data52360';
    }

    public function getDatainfoPassword(): string
    {
        return 'Datainfo@17';
    }

    public function getPis(): string
    {
        return '';
    }
};

$tz = new \DateTimeZone('America/Sao_Paulo');
$balance = $sistema->getBalance($user, new \DateTime('2019-04-01', $tz), new \DateTime('2019-04-30', $tz));

echo 'A trabalhar: ', $balance->getTimeToWork(), PHP_EOL;
echo 'Trabalhadas: ', $balance->getWorkedTime(), PHP_EOL;
