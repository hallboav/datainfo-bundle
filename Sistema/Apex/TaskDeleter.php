<?php

use GuzzleHttp\ClientInterface;
use Symfony\Component\Validator\Validation;
use Hallboav\DatainfoBundle\Sistema\Task\Task;
use Symfony\Component\Validator\Constraints as Assert;
use Hallboav\DatainfoBundle\Sistema\Client\Middleware\JsonResponse;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class TaskDeleter
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
     * Exclui um registro de ponto.
     *
     * @param DatainfoUserInterface $user
     * @param string                $instance
     * @param string                $ajaxId
     * @param string                $performedTaskId
     * @param Task                  $task
     *
     * @return string
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \DomainException          Quando o Service não exclui o ponto e também não retorna nenhum erro.
     */
    public function delete(DatainfoUserInterface $user, string $instance, string $ajaxId, string $performedTaskId, Task $task): string
    {
        $parameters = [
            sprintf('p_request=PLUGIN=%s', $ajaxId),
            'p_flow_id=104',
            'p_flow_step_id=100',
            sprintf('p_instance=%s', $instance),
            'p_arg_names=P100_NUMSEQESFORCO',
            sprintf('p_arg_values=%s', $performedTaskId),
            'p_arg_names=P100_F_APEX_USER',
            sprintf('p_arg_values=%s', strtoupper($user->getDatainfoUsername())),
            'p_arg_names=P100_DATAESFORCO',
            sprintf('p_arg_values=%s', $task->getDate()->format('d/m/Y')),
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

        // Se tiver SYSMSG
        if ('' !== $json['item'][0]['value']) {
            return $json['item'][0]['value'];
        }

        if ('TRUE' === $json['item'][2]['value']) {
            return 'OK';
        }

        throw new \DomainException('O Service não excluiu o lançamento e também não apresentou nenhum erro');
    }

    /**
     * Cria constraint da resposta do Service ao excluir ponto.
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
                    'id' => new Assert\IdenticalTo('P100_HORASTRABALHADAS'),
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
