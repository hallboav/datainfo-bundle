<?php

namespace Hallboav\DatainfoBundle\Sistema\Crawler;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class LoginPageCrawler extends AbstractPageCrawler
{
    /**
     * {@inheritDoc}
     */
    public function getUri(): string
    {
        return '/apex/f?p=104:LOGIN';
    }

    /**
     * {@inheritDoc}
     */
    public function getFormButtonText(): string
    {
        return 'Conectar';
    }

    /**
     * Obtém instance (p_instance) obtido na tela de login.
     *
     * @return string
     */
    public function getInstance(): string
    {
        if (null === $this->form) {
            $this->form = $this->getForm();
        }

        return $this->form->get('p_instance')->getValue();
    }
}
