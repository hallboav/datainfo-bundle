<?php

namespace Hallboav\DatainfoBundle\Sistema\Activity;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class ActivityCollection implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $activities = [];

    /**
     * Construtor.
     *
     * @param Activity[] $activities
     */
    public function __construct(array $activities = [])
    {
        foreach ($activities as $activity) {
            $this->add($activity);
        }
    }

    public function add(Activity $activity): self
    {
        $this->activities[] = $activity;

        return $this;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->activities);
    }
}
