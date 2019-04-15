<?php

namespace Hallboav\DatainfoBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class LaunchData
{
    /**
     * @var string|null
     *
     * @Assert\NotBlank()
     */
    private $activityId;

    /**
     * @var array|null
     *
     * @Assert\NotBlank()
     */
    private $performedTasks;

    /**
     * Obtém ID da atividade.
     *
     * @return string|null
     */
    public function getActivityId(): ?string
    {
        return $this->activityId;
    }

    /**
     * Configura ID da atividade.
     *
     * @param string|null $activityId
     *
     * @return self
     */
    public function setActivityId(?string $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    /**
     * Obtém tarefas realizadas.
     *
     * @return array|null
     */
    public function getPerformedTasks(): ?array
    {
        return $this->performedTasks;
    }

    /**
     * Configura tarefas realizadas.
     *
     * @param array|null $performedTasks
     *
     * @return self
     */
    public function setPerformedTasks(?array $performedTasks): self
    {
        $this->performedTasks = $performedTasks;

        return $this;
    }
}
