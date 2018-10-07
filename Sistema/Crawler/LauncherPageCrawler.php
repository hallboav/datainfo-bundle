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
    protected function getUri(string $instance): string
    {
        return sprintf('/apex/f?p=104:100:%s', $instance);
    }

    /**
     * Obtém o ajaxId usado para obter as atividades.
     *
     * @param string $contents
     *
     * @return string
     */
    public function getAjaxIdForActivitiesFetching(string $contents): string
    {
        $leftRegExp = '\#P100_SEQ_ESFORCO\"\,';

        return $this->getAjaxId($contents, $leftRegExp, '');
    }

    /**
     * Obém o ajaxId usado para lançamento do realizado.
     *
     * @param string $contents
     *
     * @return string
     */
    public function getAjaxIdForLaunching(string $contents): string
    {
        $rightRegExp = '\,\"attribute01\"\:\".*P100_DATAESFORCO\,\#P100_DESCRICAO';

        return $this->getAjaxId($contents, '', $rightRegExp);
    }

    /**
     * Obtém os projetos.
     *
     * @param string $contents
     *
     * @return array
     */
    public function getProjects(string $contents): array
    {
        $options = [];
        $crawler = new Crawler($contents);
        $crawler->filter('#P100_PROJETOUSUARIO option:not([value=""])')->each(function (Crawler $option) use (&$options) {
            $options[] = new Project($option->attr('value'), $option->text());
        });

        return $options;
    }
}
