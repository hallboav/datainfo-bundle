<?php

namespace Hallboav\DatainfoBundle\Sistema\Crawler;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class QueryPageCrawler extends AbstractPageCrawler
{
    /**
     * {@inheritDoc}
     */
    public function getUri(): string
    {
        return sprintf('/apex/f?p=104:10:%s::NO::P10_W_DAT_INICIO,P10_W_DAT_TERMINO:', $this->instance);
    }

    /**
     * {@inheritDoc}
     */
    public function getFormButtonText(): string
    {
        return 'Consultar';
    }

    /**
     * Obtém ajaxId para consultar o saldo.
     *
     * @return string
     */
    public function getAjaxIdForBalanceChecking(): string
    {
        if (null === $this->contents) {
            $this->crawl();
        }

        $rightRegExp = '\,"attribute01":"\#P10_W_DAT_INICIO';

        return $this->getAjaxId($this->contents, '', $rightRegExp);
    }
}
