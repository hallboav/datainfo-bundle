<?php

namespace Hallboav\DatainfoBundle\Sistema\Apex;

use GuzzleHttp\ClientInterface;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Authenticator
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Construtor.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
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
     *
     * @return void
     *
     * @throws \InvalidArgumentException Quando o usuário e/ou a senha estão incorretos.
     */
    public function authenticate(DatainfoUserInterface $user, string $instance, string $salt, string $protected): void
    {
        $parameters = [
            'p_json' => json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P101_USERNAME', 'v' => $user->getDatainfoUsername()],
                        ['n' => 'P101_PASSWORD', 'v' => $user->getDatainfoPassword()],
                    ],
                    'protected' => $protected,
                ]
            ]),
            'p_flow_id' => '104',
            'p_flow_step_id' => '101',
            'p_instance' => $instance,
        ];

        $response = $this->client->post('/apex/wwv_flow.accept', [
            'form_params' => $parameters,
            'allow_redirects' => [
                'max' => 1,
            ],
        ]);

        if (false === strpos($response->getBody()->getContents(), 'Sair')) {
            throw new \InvalidArgumentException('Usuário e/ou senha inválido(s)');
        }
    }
}
