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
     * @param string             $instance  p_instance.
     * @param string             $ajaxId    ajaxIdentifier.
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     *
     * @return Balance|null Instância de Balance contendo as horas trabalhadas e horas a trabalhar ou
     *                      nulo quando não há nenhum lançamento de realizado no período informado.
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \UnexpectedValueException Quando a resposta do Service não traz os valores esperados.
     */
    public function check(string $instance, string $ajaxId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): ?Balance
    {
        $parameters = [
            sprintf('p_request=PLUGIN=%s', $ajaxId),
            'p_flow_id=104',
            'p_flow_step_id=10',
            sprintf('p_instance=%s', $instance),
            'p_arg_names=P10_W_DAT_INICIO',
            sprintf('p_arg_values=%s', $startDate->format('d/m/Y')),
            'p_arg_names=P10_W_DAT_TERMINO',
            sprintf('p_arg_values=%s', $endDate->format('d/m/Y')),
        ];

        $response = $this->client->post('/apex/wwv_flow.show', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => implode('&', $parameters),
        ]);

        if (!($response instanceof JsonResponse)) {
            throw new \UnexpectedValueException('A resposta deve ser do tipo JSON.');
        }

        $json = $response->getJson();

        if (!isset($json['item'][2]['value'], $json['item'][3]['value'])) {
            throw new \UnexpectedValueException('A resposta do Service é insuficiente para determinar saldo de horas.');
        }

        if ('' === $json['item'][2]['value'] || '' === $json['item'][3]['value']) {
            return null;
        }

        return new Balance($json['item'][2]['value'], $json['item'][3]['value']);
    }
}
