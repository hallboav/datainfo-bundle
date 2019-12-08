<?php

namespace Hallboav\DatainfoBundle\Sistema\Apex;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Hallboav\DatainfoBundle\Sistema\Activity\Activity;
use Hallboav\DatainfoBundle\Sistema\Effort\EffortType;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Hallboav\DatainfoBundle\Sistema\Task\Task;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Launcher
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
     * Lança um ponto.
     *
     * A porcentagem sempre será 99%.
     *
     * @param DatainfoUserInterface $user       Usuário da Datainfo.
     * @param Task                  $task       Tarefa contendo data, hora inicial, hora final, ticket e descrição.
     * @param Activity              $activity   Execução da sprint, planejamento da sprint, etc.
     * @param EffortType            $effortType Normal, extra, viagem, etc.
     * @param string                $instance   p_instance.
     * @param string                $ajaxId     ajaxIdentifier.
     * @param string                $salt
     * @param string                $protected
     *
     * @return string Mensagem.
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \DomainException          Quando o Service não lança o ponto e também não retorna nenhum erro.
     */
    public function launch(DatainfoUserInterface $user, Task $task, Activity $activity, EffortType $effortType, string $instance, string $ajaxId, string $salt, string $protected): string
    {
        $parameters = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '100',
            'p_instance' => $instance,
            'p_request' => sprintf('PLUGIN=%s', urlencode($ajaxId)),
            'p_json' => json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P100_F_APEX_USER',       'v' => strtoupper($user->getDatainfoUsername())],
                        ['n' => 'P100_DATAESFORCO',       'v' => $task->getDate()->format('d/m/Y')],
                        ['n' => 'P100_DESCRICAO',         'v' => $task->getDescription()],
                        ['n' => 'P100_PROJETOUSUARIO',    'v' => $activity->getProject()->getId()],
                        ['n' => 'P100_SEQORDEMSERVICO',   'v' => $activity->getId()],
                        ['n' => 'P100_HORINICIO',         'v' => $task->getStartTime()->format('H:i')],
                        ['n' => 'P100_HORFIM',            'v' => $task->getEndTime()->format('H:i')],
                        ['n' => 'P100_TIPOESFORCO',       'v' => $effortType->getId()],
                        ['n' => 'P100_CHAMADO',           'v' => $task->getTicket()],
                        ['n' => 'P100_PERCONCLUSAO',      'v' => '99'],
                        ['n' => 'P100_DIAFUTURO',         'v' => 'N'],
                        ['n' => 'P100_PERMISSAO',         'v' => 'S'],
                        ['n' => 'P100_TIP_ORDEM_SERVICO', 'v' => '1'],
                    ],
                    'protected' => $protected,
                ],
            ]),
        ];

        $response = $this->client->request('POST', '/apex/wwv_flow.ajax', [
            'body' => $parameters,
        ]);

        $json = $response->toArray();

        $validator = Validation::createValidator();
        $violations = $validator->validate($json, $this->createConstraint());

        if (count($violations) > 0) {
            return (string) $violations;
        }

        // Se tiver P100_SYSMSG
        if (isset($json['item'][0]['value']) && '' !== $json['item'][0]['value']) {
            return $json['item'][0]['value'];
        }

        // Se tiver P100_MSG
        if (isset($json['item'][1]['value']) && '' !== $json['item'][1]['value']) {
            return $json['item'][1]['value'];
        }

        // P100_SALVOU
        if ('TRUE' === $json['item'][2]['value']) {
            return 'OK';
        }

        throw new \DomainException('O Service não salvou o lançamento e também não apresentou nenhum erro');
    }

    /**
     * Cria constraint da resposta do Service ao lançar ponto.
     *
     * @return Assert\Collection
     */
    private function createConstraint(): Assert\Collection
    {
        return new Assert\Collection([
            'item' => new Assert\Collection([
                0 => new Assert\Collection([
                    'allowMissingFields' => true,
                    'fields' => [
                        'id' => new Assert\IdenticalTo('P100_SYSMSG'),
                        'value' => new Assert\Type('string'),
                    ],
                ]),
                1 => new Assert\Collection([
                    'allowMissingFields' => true,
                    'fields' => [
                        'id' => new Assert\IdenticalTo('P100_MSG'),
                        'value' => new Assert\Type('string'),
                    ],
                ]),
                2 => new Assert\Collection([
                    'allowMissingFields' => true,
                    'fields' => [
                        'id' => new Assert\IdenticalTo('P100_SALVOU'),
                        'value' => [
                            new Assert\Type('string'),
                            new Assert\Choice(['TRUE', 'FALSE', '']),
                        ],
                    ],
                ]),
            ]),
        ]);
    }
}
