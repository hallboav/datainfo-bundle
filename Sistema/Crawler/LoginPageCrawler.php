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
     * @var Form
     */
    private $form;

    /**
     * @var Crawler
     */
    private $crawler;

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
     * @return string
     */
    public function getInstance(): string
    {
        if (null === $this->contents) {
            $this->crawl();
        }

        if (null === $this->form) {
            $this->form = $this->getForm();
        }

        return $this->form->get('p_instance')->getValue();
    }

    /**
     * Obtém o salt contido na tela de login.
     *
     * @return string
     */
    public function getSalt(): string
    {
        if (null === $this->crawler) {
            $this->crawler = $this->getCrawler();
        }

        return $this->crawler->filter('input#pSalt')->attr('value');
    }

    /**
     * Obtém o protected contido na tela de login.
     *
     * @return string
     */
    public function getProtected(): string
    {
        if (null === $this->crawler) {
            $this->crawler = $this->getCrawler();
        }

        return $this->crawler->filter('input#pPageItemsProtected')->attr('value');
    }

    /**
     * Obtém o formulário.
     *
     * @return Form
     */
    private function getForm(): Form
    {
        if (null === $this->crawler) {
            $this->crawler = $this->getCrawler();
        }

        return $this->crawler->selectButton(self::CONNECT_BUTTON_LABEL)->form();
    }

    /**
     * Obtém instância de Crawler.
     *
     * @return Crawler
     */
    private function getCrawler(): Crawler
    {
        if (null === $this->contents) {
            $this->crawl();
        }

        $uri = sprintf('%s%s', $this->client->getConfig('base_uri'), self::LOGIN_URI);

        return new Crawler($this->contents, $uri);
    }
}
