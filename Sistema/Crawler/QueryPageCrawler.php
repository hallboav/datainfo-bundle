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
    public function getUri(string $instance): string
    {
        return sprintf('/apex/f?p=104:10:%s::NO::P10_W_DAT_INICIO,P10_W_DAT_TERMINO:', $instance);
    }

    /**
     * Obtém ajaxId para consultar o saldo.
     *
     * @param string $contents
     *
     * @return string
     */
    public function getAjaxIdForBalanceChecking(string $contents): string
    {
        $rightRegExp = '\,\"attribute01\"\:\".*P10_W_DAT_INICIO';

        return $this->getAjaxId($contents, '', $rightRegExp);
    }
}
