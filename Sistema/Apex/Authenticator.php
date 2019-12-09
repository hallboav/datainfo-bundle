<?php

namespace Hallboav\DatainfoBundle\Sistema\Apex;

use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
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
     * @param string                $parsedOraWwvApp104Cookie
     *
     * @return string
     *
     * @throws \InvalidArgumentException Quando o usuário e/ou a senha estão incorretos.
     */
    public function authenticate(DatainfoUserInterface $user, string $instance, string $salt, string $protected, string $parsedOraWwvApp104Cookie): string
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

        $requestHeaders = [
            'Cookie' => $parsedOraWwvApp104Cookie,
        ];

        $response = $this->client->request('POST', '/apex/wwv_flow.accept', [
            'headers' => $requestHeaders,
            'max_redirects' => 0,
            'body' => $parameters,
        ]);

        $responseHeaders = $response->getHeaders($throw = false);
        $cookieJar = new BrowserKitCookieJar();

        foreach ($responseHeaders['set-cookie'] as $cookieStr) {
            $cookie = BrowserKitCookie::fromString($cookieStr);
            $cookieJar->set($cookie);
        }

        $oraWwvApp104CookieUpdated = $cookieJar->get('ORA_WWV_APP_104');
        $parsedOraWwvApp104CookieUpdated = sprintf(
            '%s=%s',
            $oraWwvApp104CookieUpdated->getName(),
            $oraWwvApp104CookieUpdated->getValue()
        );

        $requestHeaders = [
            'Cookie' => $parsedOraWwvApp104CookieUpdated,
        ];

        $response = $this->client->request('GET', $response->getInfo('redirect_url'), [
            'headers' => $requestHeaders,
            'max_redirects' => 0,
        ]);

        if (false === strpos($response->getContent(), 'Sair')) {
            throw new \InvalidArgumentException('Usuário e/ou senha inválido(s)');
        }

        return $parsedOraWwvApp104CookieUpdated;
    }
}
