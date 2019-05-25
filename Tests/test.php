<?php

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

require dirname(__DIR__) . '/vendor/autoload.php';

$client = new Client([
    'cookies' => true,
    'base_uri' => 'http://sistema.datainfo.inf.br',
    'connect_timeout' => 30,
]);

///////////
// Login //
///////////
echo 'Buscando informações para o login...', PHP_EOL;
$loginUri = '/apex/f?p=104:LOGIN';
$response = $client->get($loginUri);
echo 'Pronto.', PHP_EOL;
$contents = $response->getBody()->getContents();

$uri = sprintf('%s%s', $client->getConfig('base_uri'), $loginUri);
$crawler = new Crawler($contents, $uri);
$form = $crawler->selectButton('Conectar')->form();

$instance = $form->get('p_instance')->getValue();
$salt = $crawler->filter('input#pSalt')->attr('value');
$protected = $crawler->filter('input#pPageItemsProtected')->attr('value');
// $rowVersion = $crawler->filter('input#pPageItemsRowVersion')->attr('value');

$parameters = [
    'p_json' => json_encode([
        'salt' => $salt,
        'pageItems' => [
            'itemsToSubmit' => [
                ['n' => 'P101_USERNAME', 'v' => 'data52360'],
                ['n' => 'P101_PASSWORD', 'v' => ''],
                // ['n' => 'P0_PW',         'v' => ''],
            ],
            'protected' => $protected,
            // 'rowVersion' => $rowVersion,
        ]
    ]),
    'p_flow_id' => '104',
    'p_flow_step_id' => '101',
    'p_instance' => $instance,
    // 'p_page_submission_id' => $form->get('p_page_submission_id')->getValue(),
    // 'p_request' => 'LOGIN',
    // 'p_reload_on_submit' => $form->get('p_reload_on_submit')->getValue(),
];

echo 'Fazendo login...', PHP_EOL;
$response = $client->post('/apex/wwv_flow.accept', [
    'form_params' => $parameters,
    'allow_redirects' => [
        'max' => 1,
    ],
]);

if (false === strpos($response->getBody()->getContents(), 'Sair')) {
    throw new \InvalidArgumentException('Usuário e/ou senha inválido(s)');
}

echo 'Logado com sucesso!', PHP_EOL;

///////////
// Query //
///////////

echo 'Buscando informações para a consulta de saldo...', PHP_EOL;
$queryPageUri = sprintf('/apex/f?p=104:10:%s::NO::P10_W_DAT_INICIO,P10_W_DAT_TERMINO:', $instance);
$response = $client->get($queryPageUri);
echo 'Pronto.', PHP_EOL;
$contents = $response->getBody()->getContents();

if (!preg_match('#"ajaxIdentifier":"(?P<ajax_id>.+)"\,"attribute01":"\#P10_W_DAT_INICIO#', $contents, $matches)) {
    throw new \LengthException('ajaxIdentifier não encontrado');
}

$ajaxId = $matches['ajax_id'];

$uri = sprintf('%s%s', $client->getConfig('base_uri'), $queryPageUri);
$crawler = new Crawler($contents, $uri);
$salt = $crawler->filter('input#pSalt')->attr('value');
$protected = $crawler->filter('input#pPageItemsProtected')->attr('value');
// $rowVersion = $crawler->filter('input#pPageItemsRowVersion')->attr('value');

$parameters = [
    'p_flow_id' => '104',
    'p_flow_step_id' => '10',
    'p_instance' => $instance,
    'p_request' => sprintf('PLUGIN=%s', urlencode($ajaxId)),
    'p_json' => json_encode([
        'salt' => $salt,
        'pageItems' => [
            'itemsToSubmit' => [
                ['n' => 'P10_W_DAT_INICIO',  'v' => '01/04/2019'],
                ['n' => 'P10_W_DAT_TERMINO', 'v' => '30/04/2019'],
                // ['n' => 'P10_W_SIG_PROJE',   'v' => ''],
                // ['n' => 'P10_W_TIP_ESFORCO', 'v' => ''],
            ],
            'protected' => $protected,
            // 'rowVersion' => $rowVersion,
        ],
    ]),
];

echo 'Buscando saldo...', PHP_EOL;
$response = $client->post('/apex/wwv_flow.ajax', [
    'form_params' => $parameters,
]);

$data = json_decode($response->getBody()->getContents(), true);
echo 'A trabalhar: ', $data['item'][3]['value'], PHP_EOL;
echo 'Trabalhadas: ', $data['item'][2]['value'], PHP_EOL;

////////////////
// Lançamento //
////////////////

echo 'Buscando informações para o lançamento de realizado...', PHP_EOL;
$launchPageUri = sprintf('/apex/f?p=104:100:%s', $instance);
$response = $client->get($launchPageUri);
echo 'Pronto.', PHP_EOL;
$contents = $response->getBody()->getContents();

if (!preg_match('#"ajaxIdentifier":"(?P<ajax_id>.+)"\,"attribute01":"\#P100_NUMSEQESFORCO\,\#P100_USUARIO\,\#P100_DATAESFORCO\,\#P100_DESCRICAO#', $contents, $matches)) {
    throw new \LengthException('ajaxIdentifier não encontrado');
}

$ajaxId = $matches['ajax_id'];

$uri = sprintf('%s%s', $client->getConfig('base_uri'), $launchPageUri);
$crawler = new Crawler($contents, $uri);
$salt = $crawler->filter('input#pSalt')->attr('value');
$protected = $crawler->filter('input#pPageItemsProtected')->attr('value');
// $rowVersion = $crawler->filter('input#pPageItemsRowVersion')->attr('value');

$parameters = [
    'p_flow_id' => '104',
    'p_flow_step_id' => '100',
    'p_instance' => $instance,
    'p_request' => sprintf('PLUGIN=%s', urlencode($ajaxId)),
    'p_json' => json_encode([
        'salt' => $salt,
        'pageItems' => [
            'itemsToSubmit' => [
                ['n' => 'P100_NUMSEQESFORCO',     'v' => ''],
                // ['n' => 'P100_SYSMSG',            'v' => '', 'ck' => 'BCkjw5aVW_kbhuVDvkYDGE6P6zjXyhWdDeWdzIiyLDAxjkjphibKlwxeDYoDN6FeNJj_HVB5V98cci3J4sENzQ'],
                ['n' => 'P100_DIAFUTURO',         'v' => 'N'],
                ['n' => 'P100_PROGRESSO',         'v' => ''],
                // ['n' => 'P100_F_APEX_USER',       'v' => 'DATA52360'],
                ['n' => 'P100_PERMISSAO',         'v' => 'S'],
                ['n' => 'P100_DATAESFORCO',       'v' => '22/05/2019'],
                ['n' => 'P100_DESCRICAO',         'v' => 'Testing'],
                ['n' => 'P100_PROJETOUSUARIO',    'v' => 'PJ1283'],
                ['n' => 'P100_SEQORDEMSERVICO',   'v' => '219686'],
                ['n' => 'P100_HORINICIO',         'v' => '12:00'],
                ['n' => 'P100_HORFIM',            'v' => '18:00'],
                ['n' => 'P100_PERCONCLUSAO',      'v' => '99'],
                ['n' => 'P100_TIPOESFORCO',       'v' => '1'],
                ['n' => 'P100_TIP_ORDEM_SERVICO', 'v' => '1'],
                ['n' => 'P100_CHAMADO',           'v' => 'EM-5456'],
            ],
            'protected' => $protected,
            // 'rowVersion' => $rowVersion,
        ],
    ]),
];

echo 'Lançando...', PHP_EOL;
$response = $client->post('/apex/wwv_flow.ajax', [
    'form_params' => $parameters,
]);

$data = json_decode($response->getBody()->getContents(), true);
echo 'TRUE' === $data['item'][2]['value'] ? 'Lançado com sucesso!' : 'Falha ao lançar.';
