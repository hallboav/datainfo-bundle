<?php

namespace Hallboav\DatainfoBundle\Sistema\Balance;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Balance implements \JsonSerializable
{
    /**
     * @var string
     */
    private $workedTime;

    /**
     * @var string
     */
    private $timeToWork;

    /**
     * Construtor.
     *
     * @param string $workedTime
     * @param string $timeToWork
     */
    public function __construct(string $workedTime, string $timeToWork)
    {
        $this->workedTime = $workedTime;
        $this->timeToWork = $timeToWork;
    }

    /**
     * Obtém o tempo trabalhado.
     *
     * @return string
     */
    public function getWorkedTime(): string
    {
        return $this->workedTime;
    }

    /**
     * Obtém o tempo à trabalhar.
     *
     * @return string
     */
    public function getTimeToWork(): string
    {
        return $this->timeToWork;
    }

    /**
     * Obtém o objeto em formato de array.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'worked_time' => $this->getWorkedTime(),
            'worked_time_timestamp' => $this->toTimestamp($this->getWorkedTime()),
            'time_to_work' => $this->getTimeToWork(),
            'time_to_work_timestamp' => $this->toTimestamp($this->getTimeToWork()),
        ];
    }

    /**
     * Converte string em timestamp.
     *
     * @return int
     */
    private function toTimestamp(string $time): int
    {
        $parts = preg_split('#:#', $time);

        if (2 !== count($parts)) {
            throw new \RuntimeException();
        }

        $interval = new \DateInterval(sprintf('PT%dH%dM', $parts[0], $parts[1]));

        return mktime(
            $interval->format('%h'),
            $interval->format('%i'),
            $interval->format('%s'),
            $interval->format('%m'),
            $interval->format('%d'),
            0
        );
    }
}
