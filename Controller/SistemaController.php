<?php

namespace Hallboav\DatainfoBundle\Controller;

use Hallboav\DatainfoBundle\Entity\Launch;
use Hallboav\DatainfoBundle\Form\Type\LaunchType;
use Hallboav\DatainfoBundle\Sistema\Activity\Activity;
use Hallboav\DatainfoBundle\Sistema\Activity\Project;
use Hallboav\DatainfoBundle\Sistema\Effort\EffortType;
use Hallboav\DatainfoBundle\Sistema\Effort\FilteringEffortType;
use Hallboav\DatainfoBundle\Sistema\Sistema;
use Hallboav\DatainfoBundle\Sistema\Task\Task;
use Hallboav\DatainfoBundle\Sistema\Task\TaskCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class SistemaController extends AbstractController
{
    /**
     * Obtém todas atividades.
     *
     * @param Sistema $sistema
     *
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     *
     * @return Response
     */
    public function activities(Sistema $sistema): Response
    {
        $activities = $this->getUserActivities($sistema);

        return $this->json([
            'activities' => $activities,
        ]);
    }

    /**
     * Obtém o saldo em formato JSON.
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param Sistema   $sistema
     *
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @ParamConverter("startDate", options={"format": "Y-m-d"})
     * @ParamConverter("endDate", options={"format": "Y-m-d"})
     *
     * @return Response
     */
    public function balance(\DateTime $startDate, \DateTime $endDate, Sistema $sistema): Response
    {
        $balance = $sistema->getBalance($this->getUser(), $startDate, $endDate);

        return $this->json([
            'balance' => $balance,
        ]);
    }

    /**
     * Obtém o relatório de pontos lançados.
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param Sistema   $sistema
     *
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @ParamConverter("startDate", options={"format": "Y-m-d"})
     * @ParamConverter("endDate", options={"format": "Y-m-d"})
     *
     * @return Response
     */
    public function widgetReport(\DateTime $startDate, \DateTime $endDate, Sistema $sistema): Response
    {
        $filteringEfforType = new FilteringEffortType('todos');
        $report = $sistema->getWidgetReport($this->getUser(), $startDate, $endDate, $filteringEfforType);

        return $this->json([
            'widget_report' => $report,
        ]);
    }

    /**
     * Lança os pontos.
     *
     * @param Request $request
     * @param Sistema $sistema
     *
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     *
     * @return Response
     */
    public function launchPerformedTasks(Request $request, Sistema $sistema): Response
    {
        $form = $this->createForm(LaunchType::class, null, [
            'csrf_protection' => false,
        ]);

        $data = $request->request->all();
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $activityId = $data->getActivityId();
            if (null === $activity = $this->getUserActivityById($sistema, $activityId)) {
                throw new UnprocessableEntityHttpException(sprintf('O atividade "%s" não é atividade do usuário logado', $activityId));
            }

            $tasks = new TaskCollection();
            foreach ($data->getPerformedTasks() as $taskData) {
                $task = new Task(
                    $taskData->getDate(),
                    $taskData->getStartTime(),
                    $taskData->getEndTime(),
                    $taskData->getDescription(),
                    $taskData->getTicket()
                );

                $tasks->add($task);
            }

            $effortType = new EffortType('normal');
            $messages = $sistema->launchPerformedTasks($this->getUser(), $activity, $tasks, $effortType);

            return $this->json([
                'messages' => $messages,
            ]);
        }

        throw new BadRequestHttpException((string) $form->getErrors($errorsOfChildForms = true, $flatten = false));
    }

    /**
     * Obtém as atividades do usuário logado.
     *
     * @param Sistema $sistema
     *
     * @return array
     */
    private function getUserActivities(Sistema $sistema): array
    {
        $user = $this->getUser();
        $projects = $sistema->getProjects($user);

        $activities = [];
        foreach ($projects as $project) {
            $projectActivities = $sistema->getActivities($user, $project);

            foreach ($projectActivities as $activity) {
                $activities[] = $activity;
            }
        }

        return $activities;
    }

    /**
     * Obtém uma atividade do usuário logado a partir de um ID de uma atividade.
     *
     * @param Sistema $sistema
     * @param string  $activityId
     *
     * @return array|null
     */
    private function getUserActivityById(Sistema $sistema, string $activityId): ?Activity
    {
        $activities = $this->getUserActivities($sistema);
        foreach ($activities as $activity) {
            if ($activityId === $activity->getId()) {
                return $activity;
            }
        }

        return null;
    }
}
