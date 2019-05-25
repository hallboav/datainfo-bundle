<?php

namespace Hallboav\DatainfoBundle\Sistema\Task;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class TaskCollection implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $tasks;

    /**
     * Construtor.
     *
     * @param Task[] $tasks
     */
    public function __construct(array $tasks = [])
    {
        foreach ($tasks as $task) {
            $this->add($task);
        }
    }

    public function add(Task $task): self
    {
        $this->tasks[] = $task;

        return $this;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->tasks);
    }
}
