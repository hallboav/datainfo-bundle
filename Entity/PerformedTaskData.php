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
     * @Assert\NotBlank(message="Data não pode ser vazia")
     * @Assert\DateTime(message="A data deve estar no formato válido", format="Y-m-d\T00:00:00P")
     */
    private $date;

    /**
     * @var \DateTimeInterface|null
     *
     * @Assert\NotBlank(message="Hora inicial não pode ser vazia")
     * @Assert\DateTime(message="A hora inicial deve estar no formato válido", format="Y-m-d\TH:i:sP")
     */
    private $startTime;

    /**
     * @var \DateTimeInterface|null
     *
     * @Assert\NotBlank(message="Hora final não pode ser vazia")
     * @Assert\DateTime(message="A hora final deve estar no formato válido", format="Y-m-d\TH:i:sP")
     */
    private $endTime;

    /**
     * @var string|null
     *
     * @Assert\NotBlank(message="Descrição não pode ser vazia")
     * @Assert\Type(message="Descrição deve ser do tipo string", type="string")
     * @Assert\Length(message="Mensagem deve ter no máximo 500 caracteres", max=500)
     */
    private $description;

    /**
     * @var string|null
     *
     * @Assert\NotBlank(message="O ticket não pode ser vazio")
     * @Assert\Type(message="O ticket deve ser do tipo string", type="string")
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
