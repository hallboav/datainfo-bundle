<?php

namespace Hallboav\DatainfoBundle\Sistema\Apex;

use GuzzleHttp\ClientInterface;
use Hallboav\DatainfoBundle\Sistema\Client\Middleware\JsonResponse;
use Hallboav\DatainfoBundle\Sistema\Balance\Balance;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class BalanceChecker
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
     * Consulta o saldo.
     *
     * Um array com as chaves worked_time e time_to_work é retornado em caso de sucesso.
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @param string             $instance  p_instance.
     * @param string             $ajaxId    ajaxIdentifier.
     * @param string             $salt
     * @param string             $protected
     *
     * @return Balance|null Instância de Balance contendo as horas trabalhadas e horas a trabalhar ou
     *                      nulo quando não há nenhum lançamento de realizado no período informado.
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \UnexpectedValueException Quando a resposta do Service não traz os valores esperados.
     */
    public function check(\DateTimeInterface $startDate, \DateTimeInterface $endDate, string $instance, string $ajaxId, string $salt, string $protected): ?Balance
    {
        $parameters = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '10',
            'p_instance' => $instance,
            'p_request' => sprintf('PLUGIN=%s', urlencode($ajaxId)),
            'p_json' => json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P10_W_DAT_INICIO',  'v' => $startDate->format('d/m/Y')],
                        ['n' => 'P10_W_DAT_TERMINO', 'v' => $endDate->format('d/m/Y')],
                        // ['n' => 'P10_W_SIG_PROJE',   'v' => ''],
                        // ['n' => 'P10_W_TIP_ESFORCO', 'v' => ''],
                    ],
                    'protected' => $protected,
                ],
            ]),
        ];

        $response = $this->client->post('/apex/wwv_flow.ajax', [
            'form_params' => $parameters,
        ]);

        if (!($response instanceof JsonResponse)) {
            throw new \UnexpectedValueException('A resposta deve ser do tipo JSON.');
        }

        $json = $response->getJson();

        if (!isset($json['item'][2]['value'], $json['item'][3]['value'])) {
            return null;
        }

        return new Balance($json['item'][2]['value'], $json['item'][3]['value']);
    }
}
