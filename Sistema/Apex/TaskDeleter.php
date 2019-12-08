<?php

use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Hallboav\DatainfoBundle\Sistema\Task\Task;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class TaskDeleter
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
     * Exclui um registro de ponto.
     *
     * @param DatainfoUserInterface $user
     * @param Task                  $task
     * @param string                $performedTaskId
     * @param string                $instance
     * @param string                $ajaxId
     * @param string                $salt
     * @param string                $protected
     *
     * @return string
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \DomainException          Quando o Service não exclui o ponto e também não retorna nenhum erro.
     */
    public function deleteTask(DatainfoUserInterface $user, Task $task, string $performedTaskId, string $instance, string $ajaxId, string $salt, string $protected): string
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
                        ['n' => 'P100_NUMSEQESFORCO', 'v' => $performedTaskId],
                        ['n' => 'P100_F_APEX_USER',   'v' => strtoupper($user->getDatainfoUsername())],
                        ['n' => 'P100_DATAESFORCO',   'v' => $task->getDate()->format('d/m/Y')],
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
