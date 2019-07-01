<?php

namespace Hallboav\DatainfoBundle\Sistema\Crawler;

use Symfony\Component\DomCrawler\Crawler;
use Hallboav\DatainfoBundle\Sistema\Activity\Project;
use Hallboav\DatainfoBundle\Sistema\Activity\ProjectCollection;

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
     * {@inheritDoc}
     */
    protected function getFormButtonText(): string
    {
        return 'Salvar';
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

        $leftRegExp = '\#P100_SEQ_ESFORCO"\,';

        return $this->getAjaxId($this->contents, $leftRegExp, '');
    }

    /**
     * Obtém o ajaxId usado para lançamento do realizado.
     *
     * @return string
     */
    public function getAjaxIdForLaunching(): string
    {
        if (null === $this->contents) {
            $this->crawl();
        }

        $rightRegExp = '\,"attribute01":".*P100_DATAESFORCO\,\#P100_DESCRICAO';

        return $this->getAjaxId($this->contents, '', $rightRegExp);
    }

    /**
     * Obtém o ajaxId usado para deleção do realizado.
     *
     * @return string
     */
    public function getAjaxIdForTaskDeleting(): string
    {
        return $this->getAjaxIdForLaunching();
    }

    /**
     * Obtém os projetos.
     *
     * @return ProjectCollection
     */
    public function getProjects(): ProjectCollection
    {
        if (null === $this->contents) {
            $this->crawl();
        }

        $projects = new ProjectCollection();

        $crawler = new Crawler($this->contents);
        $crawler->filter('#P100_PROJETOUSUARIO option:not([value=""])')->each(function (Crawler $option) use ($projects) {
            $projects->add(new Project($option->attr('value'), $option->text()));
        });

        return $projects;
    }
}
