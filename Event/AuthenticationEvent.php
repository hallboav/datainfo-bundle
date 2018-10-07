<?php

namespace Hallboav\DatainfoBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class AuthenticationEvent extends Event
{
    /**
     * @var DatainfoUserInterface
     */
    protected $user;

    /**
     * Construtor.
     *
     * @param DatainfoUserInterface $user
     */
    public function __construct(DatainfoUserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * Obtém o usuário.
     *
     * @return DatainfoUserInterface
     */
    public function getUser(): DatainfoUserInterface
    {
        return $this->user;
    }
}
