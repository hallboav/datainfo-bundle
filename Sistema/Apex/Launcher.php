<?php

namespace Hallboav\DatainfoBundle\Sistema\Apex;

use GuzzleHttp\ClientInterface;
use Symfony\Component\Validator\Validation;
use Hallboav\DatainfoBundle\Sistema\Task\Task;
use Hallboav\DatainfoBundle\Sistema\Client\Middleware\JsonResponse;
use Hallboav\DatainfoBundle\Sistema\Activity\Activity;
use Hallboav\DatainfoBundle\Sistema\Effort\EffortType;
use Symfony\Component\Validator\Constraints as Assert;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Launcher
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
     * Lança um ponto.
     *
     * A porcentagem sempre será 99%.
     *
     * @param DatainfoUserInterface $user       Usuário da Datainfo.
     * @param string                $instance   p_instance.
     * @param string                $ajaxId     ajaxIdentifier.
     * @param Task                  $task       Tarefa contendo data, hora inicial, hora final, ticket e descrição.
     * @param Activity              $activity   Execução da sprint, planejamento da sprint, etc.
     * @param EffortType            $effortType Normal, extra, viagem, etc.
     *
     * @return string Mensagem.
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \DomainException          Quando o Service não lança o ponto e também não retorna nenhum erro.
     */
    public function launch(DatainfoUserInterface $user, string $instance, string $ajaxId, Task $task, Activity $activity, EffortType $effortType): string
    {
        $parameters = [
            sprintf('p_request=PLUGIN=%s', $ajaxId),
            'p_flow_id=104',
            'p_flow_step_id=100',
            sprintf('p_instance=%s', $instance),
            'p_arg_names=P100_F_APEX_USER',
            sprintf('p_arg_values=%s', strtoupper($user->getDatainfoUsername())),
            'p_arg_names=P100_PERMISSAO',
            'p_arg_values=S',
            'p_arg_names=P100_DATAESFORCO',
            sprintf('p_arg_values=%s', $task->getDate()->format('d/m/Y')),
            'p_arg_names=P100_DESCRICAO',
            sprintf('p_arg_values=%s', $task->getDescription()),
            'p_arg_names=P100_HORINICIO',
            sprintf('p_arg_values=%s', $task->getStartTime()->format('H:i')),
            'p_arg_names=P100_HORFIM',
            sprintf('p_arg_values=%s', $task->getEndTime()->format('H:i')),
            'p_arg_names=P100_CHAMADO',
            sprintf('p_arg_values=%s', $task->getTicket()),
            'p_arg_names=P100_PROJETOUSUARIO',
            sprintf('p_arg_values=%s', $activity->getProject()->getId()),
            'p_arg_names=P100_SEQORDEMSERVICO',
            sprintf('p_arg_values=%s', $activity->getId()),
            'p_arg_names=P100_TIPOESFORCO',
            sprintf('p_arg_values=%s', $effortType->getId()),
            'p_arg_names=P100_PERCONCLUSAO',
            'p_arg_values=99',
            'p_arg_names=P100_DIAFUTURO',
            'p_arg_values=N',
            'p_arg_names=P100_TIP_ORDEM_SERVICO',
            'p_arg_values=1',
        ];

        $response = $this->client->post('/apex/wwv_flow.show', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => implode('&', $parameters),
        ]);

        if (!($response instanceof JsonResponse)) {
            throw new \UnexpectedValueException('A resposta deve ser do tipo JSON.');
        }

        $json = $response->getJson();

        $validator = Validation::createValidator();
        $violations = $validator->validate($json, $this->createConstraint());

        if (count($violations) > 0) {
            return (string) $violations;
        }

        // Se tiver MSG
        if ('' !== $json['item'][1]['value']) {
            return $json['item'][1]['value'];
        }

        // Se tiver SYSMSG
        if ('' !== $json['item'][0]['value']) {
            return $json['item'][0]['value'];
        }

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
                    'id' => new Assert\IdenticalTo('P100_SYSMSG'),
                    'value' => new Assert\Type('string'),
                ]),
                1 => new Assert\Collection([
                    'id' => new Assert\IdenticalTo('P100_MSG'),
                    'value' => new Assert\Type('string'),
                ]),
                2 => new Assert\Collection([
                    'id' => new Assert\IdenticalTo('P100_SALVOU'),
                    'value' => [
                        new Assert\Type('string'),
                        new Assert\Choice(['TRUE', 'FALSE', '']),
                    ],
                ]),
            ]),
        ]);
    }
}
