<?php

namespace Hallboav\DatainfoBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class PerformedTaskData
{
    /**
     * @var \DateTimeInterface|null
     *
     * @Assert\NotBlank()
     * @Assert\Date(format=Y-m-d\T00:00:00P)
     */
    private $date;

    /**
     * @var \DateTimeInterface|null
     *
     * @Assert\NotBlank()
     * @Assert\DateTime(format=Y-m-d\TH:i:sP)
     */
    private $startTime;

    /**
     * @var \DateTimeInterface|null
     *
     * @Assert\NotBlank()
     * @Assert\DateTime(format=Y-m-d\TH:i:sP)
     */
    private $endTime;

    /**
     * @var string|null
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Length(max=500)
     */
    private $description;

    /**
     * @var string|null
     *
     * @Assert\Regex("[A-Z]+\-\d+")
     */
    private $ticket;

    /**
     * Obtém data da tarefa realizada.
     *
     * @return \DateTimeInterface|null
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Configura data da tarefa realizada.
     *
     * @param \DateTimeInterface|null $date
     *
     * @return self
     */
    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Obtém hora inicial da tarefa realizada.
     *
     * @return \DateTimeInterface|null
     */
    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    /**
     * Configura hora inicial da tarefa realizada.
     *
     * @param \DateTimeInterface|null $startTime
     *
     * @return self
     */
    public function setStartTime(?\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Obtém hora final da tarefa realizada.
     *
     * @return \DateTimeInterface|null
     */
    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    /**
     * Configura hora final da tarefa realizada.
     *
     * @param \DateTimeInterface|null $endTime
     *
     * @return self
     */
    public function setEndTime(?\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Obtém descrição da tarefa realizada.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Configura descrição da tarefa realizada.
     *
     * @param string|null $description
     *
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Obtém ticket da tarefa realizada.
     *
     * @return string|null
     */
    public function getTicket(): ?string
    {
        return $this->ticket;
    }

    /**
     * Configura ticket da tarefa realizada.
     *
     * @param string|null $ticket
     *
     * @return self
     */
    public function setTicket(?string $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }
}
