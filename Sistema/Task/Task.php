<?php

namespace Hallboav\DatainfoBundle\Sistema\Task;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Task
{
    /**
     * @var \DateTimeInterface
     */
    private $date;

    /**
     * @var \DateTimeInterface
     */
    private $startTime;

    /**
     * @var \DateTimeInterface
     */
    private $endTime;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $ticket;

    /**
     * Construtor.
     *
     * @param \DateTimeInterface $date        Data da tarefa.
     * @param \DateTimeInterface $startTime   Hora de início da tarefa.
     * @param \DateTimeInterface $endTime     Hora de conclusão da tarefa.
     * @param string             $description Descrição da tarefa.
     * @param string             $ticket      Ticket da tarefa.
     */
    public function __construct(\DateTimeInterface $date, \DateTimeInterface $startTime, \DateTimeInterface $endTime, string $description, string $ticket)
    {
        $this->date = $date;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->description = $description;
        $this->ticket = $ticket;
    }

    /**
     * Obtém a data.
     *
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Obtém a hora de início.
     *
     * @return \DateTimeInterface
     */
    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    /**
     * Obtém a hora de conclusão.
     *
     * @return \DateTimeInterface
     */
    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    /**
     * Obtém a descrição.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Obtém o ticket.
     *
     * @return string
     */
    public function getTicket(): string
    {
        return $this->ticket;
    }

    /**
     * Obtém o identificador único desta tarefa.
     *
     * @return string
     */
    public function getId(): string
    {
        $joined = <<<JOIN
{$this->date->format(\DateTime::W3C)}
{$this->startTime->format('H:i')}
{$this->endTime->format('H:i')}
{$this->description}
{$this->ticket}
JOIN;

        return sha1($joined);
    }
}
