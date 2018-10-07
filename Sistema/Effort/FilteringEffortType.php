<?php

namespace Hallboav\DatainfoBundle\Sistema\Effort;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class FilteringEffortType extends AbstractEffortType
{
    const TODOS = '';

    /**
     * {@inheritDoc}
     */
    public static function getEffortMapping(): array
    {
        return array_merge(EffortType::getEffortMapping(), ['todos' => self::TODOS]);
    }
}
