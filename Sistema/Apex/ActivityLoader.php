<?php

namespace Hallboav\DatainfoBundle\Sistema\Apex;

use GuzzleHttp\ClientInterface;
use Hallboav\DatainfoBundle\Sistema\Activity\Project;
use Hallboav\DatainfoBundle\Sistema\Activity\Activity;
use Hallboav\DatainfoBundle\Sistema\Client\Middleware\JsonResponse;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class ActivityLoader
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
     * Carrega as atividades.
     *
     * @param string  $instance
     * @param string  $ajaxId
     * @param Project $project
     *
     * @return array
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \UnexpectedValueException Quando a resposta do Service é insuficiente para ler as atividades.
     * @throws \UnexpectedValueException Quando a resposta do Service é insuficiente para ler a atividade.
     */
    public function load(string $instance, string $ajaxId, Project $project): array
    {
        $parameters = [
            sprintf('p_request=PLUGIN=%s', $ajaxId),
            'p_flow_id=104',
            'p_flow_step_id=100',
            sprintf('p_instance=%s', $instance),
            'p_arg_names=P100_PROJETOUSUARIO',
            sprintf('p_arg_values=%s', $project->getId()),
        ];

        $response = $this->client->post('/apex/wwv_flow.show', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => implode('&', $parameters),
        ]);

        if (!($response instanceof JsonResponse)) {
            throw new \UnexpectedValueException('A resposta deve ser do tipo JSON.');
        }

        $json = $response->getJson();

        if (!isset($json['values'])) {
            throw new \UnexpectedValueException('A resposta do Service é insuficiente para ler as atividades.');
        }

        $activities = [];
        foreach ($json['values'] as $value) {
            if (!isset($value['d'], $value['r'])) {
                throw new \UnexpectedValueException('A resposta do Service é insuficiente para ler a atividade.');
            }

            $activities[] = new Activity(
                $value['r'],
                html_entity_decode($value['d'], ENT_QUOTES, 'UTF-8'),
                $project
            );
        }

        return $activities;
    }
}
