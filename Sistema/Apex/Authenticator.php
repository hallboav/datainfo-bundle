<?php

namespace Hallboav\DatainfoBundle\Sistema\Apex;

use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Symfony\Component\BrowserKit\CookieJar as BrowserKitCookieJar;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Authenticator
{
    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * Construtor.
     *
     * @param HttpClientInterface $client
     */
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Autentica o usuário.
     *
     * Se tudo ocorrer bem, os cookies estarão salvos no $client.
     *
     * @param DatainfoUserInterface $user
     * @param string                $instance  p_instance.
     * @param string                $salt
     * @param string                $protected
     * @param BrowserKitCookieJar   $cookieJar
     *
     * @return void
     *
     * @throws \InvalidArgumentException Quando o usuário e/ou a senha estão incorretos.
     */
    public function authenticate(DatainfoUserInterface $user, string $instance, string $salt, string $protected, BrowserKitCookieJar $cookieJar): void
    {
        $parameters = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '101',
            'p_instance' => $instance,
            'p_json' => json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P101_USERNAME', 'v' => $user->getDatainfoUsername()],
                        ['n' => 'P101_PASSWORD', 'v' => $user->getDatainfoPassword()],
                    ],
                    'protected' => $protected,
                ],
            ]),
        ];

        $headers = [];

        $cookies = [];
        foreach ($cookieJar->allRawValues('') as $name => $value) {
            $cookies[] = sprintf('%s=%s', $name, $value);
        }

        if (0 < count($cookies)) {
            $headers['cookie'] = implode('; ', $cookies);
        }

        $response = $this->client->request('POST', '/apex/wwv_flow.accept', [
            'body' => $parameters,
            'headers' => $headers,
            'max_redirects' => 0,
        ]);

        echo 'HEADERS ANTES:', PHP_EOL;
        var_dump($headers);
        echo PHP_EOL, '------------', PHP_EOL, PHP_EOL, 'HEADERS DEPOIS:', PHP_EOL;
        var_dump($response->getHeaders()['set-cookie']);
        echo PHP_EOL, '------------', PHP_EOL, PHP_EOL, 'CONTENT:', PHP_EOL;
        var_dump($response->getContent());
        die;



        if (false === strpos($response->getContent(), 'Sair')) {
            throw new \InvalidArgumentException('Usuário e/ou senha inválido(s)');
        }
    }
}
