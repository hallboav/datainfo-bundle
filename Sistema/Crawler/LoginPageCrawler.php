<?php

namespace Hallboav\DatainfoBundle\Sistema\Crawler;

use GuzzleHttp\ClientInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class LoginPageCrawler extends AbstractPageCrawler
{
    const LOGIN_URI = '/apex/f?p=104:LOGIN';
    const CONNECT_BUTTON_LABEL = 'Conectar';

    /**
     * {@inheritDoc}
     */
    public function getUri(string $instance): string
    {
        return '/apex/f?p=104:LOGIN';
    }

    /**
     * Obtém instance (p_instance) obtido na tela de login.
     *
     * @param string $contents
     *
     * @return string
     */
    public function getInstance(string $contents): string
    {
        $form = $this->getForm($contents);

        return $form->get('p_instance')->getValue();
    }

    /**
     * Obtém checksum (p_page_checksum) contido na tela de login.
     *
     * @param string $contents
     *
     * @return string
     */
    public function getChecksum(string $contents): string
    {
        $form = $this->getForm($contents);

        return $form->get('p_page_checksum')->getValue();
    }

    /**
     * Obtém o formulário através do conteúdo.
     *
     * @param string $contents
     *
     * @return Form
     */
    private function getForm(string $contents): Form
    {
        $uri = sprintf('%s%s', $this->client->getConfig('base_uri'), self::LOGIN_URI);
        $crawler = new Crawler($contents, $uri);

        return $crawler->selectButton(self::CONNECT_BUTTON_LABEL)->form();
    }
}
