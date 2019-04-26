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
     * @param string $instance p_instance.
     * @param string $checksum p_page_checksum.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Quando o usuário e/ou a senha estão incorretos.
     */
    public function authenticate(string $instance, string $checksum, DatainfoUserInterface $user): void
    {
        $parameters = [
            'p_flow_id=104',
            'p_flow_step_id=101',
            sprintf('p_instance=%s', $instance),
            'p_arg_names=22836724431945509',
            sprintf('p_t01=%s', $user->getDatainfoUsername()),
            'p_arg_names=22836815674945509',
            sprintf('p_t02=%s', urlencode($user->getDatainfoPassword())),
            sprintf('p_page_checksum=%s', $checksum),
        ];

        $response = $this->client->post('/apex/wwv_flow.accept', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => implode('&', $parameters),
            'allow_redirects' => [
                'max' => 1,
            ],
        ]);

        if (false === strpos($response->getBody()->getContents(), 'Sair')) {
            throw new \InvalidArgumentException('Usuário e/ou senha inválido(s)');
        }
    }
}
