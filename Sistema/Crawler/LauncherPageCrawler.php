<?php

namespace Hallboav\DatainfoBundle\Sistema\Crawler;

use Symfony\Component\DomCrawler\Crawler;
use Hallboav\DatainfoBundle\Sistema\Activity\Project;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class LauncherPageCrawler extends AbstractPageCrawler
{
    /**
     * {@inheritDoc}
     */
    protected function getUri(): string
    {
        return sprintf('/apex/f?p=104:100:%s', $this->instance);
    }

    /**
     * Obtém o ajaxId usado para obter as atividades.
     *
     * @return string
     */
    public function getAjaxIdForActivitiesFetching(): string
    {
        if (null === $this->contents) {
            $this->crawl();
        }

        $leftRegExp = '\#P100_SEQ_ESFORCO\"\,';

        return $this->getAjaxId($this->contents, $leftRegExp, '');
    }

    /**
     * Obém o ajaxId usado para lançamento do realizado.
     *
     * @return string
     */
    public function getAjaxIdForLaunching(): string
    {
        if (null === $this->contents) {
            $this->crawl();
        }

        $rightRegExp = '\,\"attribute01\"\:\".*P100_DATAESFORCO\,\#P100_DESCRICAO';

        return $this->getAjaxId($this->contents, '', $rightRegExp);
    }

    /**
     * Obtém os projetos.
     *
     * @param string $contents
     *
     * @return array
     */
    public function getProjects(): array
    {
        if (null === $this->contents) {
            $this->crawl();
        }

        $options = [];
        $crawler = new Crawler($this->contents);
        $crawler->filter('#P100_PROJETOUSUARIO option:not([value=""])')->each(function (Crawler $option) use (&$options) {
            $options[] = new Project($option->attr('value'), $option->text());
        });

        return $options;
    }
}
