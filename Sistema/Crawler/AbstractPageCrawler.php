<?php

namespace Hallboav\DatainfoBundle\Sistema\Crawler;

use GuzzleHttp\ClientInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
abstract class AbstractPageCrawler
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $instance;

    /**
     * @var AdapterInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $contents;

    /**
     * Construtor.
     *
     * @param ClientInterface  $client
     * @param string           $instance            p_instance.
     * @param AdapterInterface $cache
     * @param string           $cacheKey
     * @param int              $loginCookieLifetime
     */
    public function __construct(ClientInterface $client, string $instance, AdapterInterface $cache, string $cacheKey, int $loginCookieLifetime)
    {
        $this->client = $client;
        $this->instance = $instance;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
        $this->loginCookieLifetime = $loginCookieLifetime;
    }

    /**
     * Obtém o conteúdo HTML da página.
     *
     * @return self
     */
    public function crawl(): self
    {
        $pageContentsCacheItem = $this->cache->getItem($this->cacheKey);

        if ($pageContentsCacheItem->isHit()) {
            $this->contents = $pageContentsCacheItem->get();

            return $this;
        }

        $uri = $this->getUri($this->instance);
        $response = $this->client->get($uri);
        $this->contents = $response->getBody()->getContents();

        $pageContentsCacheItem->set($this->contents);
        $pageContentsCacheItem->expiresAfter($this->loginCookieLifetime);
        $this->cache->save($pageContentsCacheItem);

        return $this;
    }

    /**
     * Obtém o URI.
     *
     * URI onde está a página para fazer o crawling.
     *
     * @return string URI.
     */
    abstract protected function getUri(): string;

    /**
     * Obtém o ajaxId.
     *
     * @param string $contents    String que contém o ajaxIdentifier.
     * @param string $leftRegExp  Expressão regular à esquerda da expressão regular do ajaxIdentifier.
     * @param string $rightRegExp Expressão regular à direita da expressão regular do ajaxIdentifier.
     *
     * @return string ajaxId.
     *
     * @throws \LengthException Quando não é possível obter o ajaxIdentifier.
     */
    protected function getAjaxId(string $subject, string $leftRegExp, string $rightRegExp): string
    {
        if (null === $ajaxId = $this->parseAjaxId($subject, $leftRegExp, $rightRegExp)) {
            throw new \LengthException('ajaxIdentifier não encontrado');
        }

        return $ajaxId;
    }

    /**
     * Obtém o valor do ajaxIdentifier.
     *
     * @param string $subject     String que contém o ajaxIdentifier.
     * @param string $leftRegExp  Expressão regular à esquerda da expressão regular do ajaxIdentifier.
     * @param string $rightRegExp Expressão regular à direita da expressão regular do ajaxIdentifier.
     *
     * @return string|null ajaxIdentifier encontrado ou null caso não encontrado.
     */
    private function parseAjaxId(string $subject, string $leftRegExp, string $rightRegExp): ?string
    {
        $pattern = sprintf('#%s"ajaxIdentifier":"(?P<ajax_id>.+)"%s#', $leftRegExp, $rightRegExp);

        if (preg_match($pattern, $subject, $matches)) {
            return $matches['ajax_id'];
        }

        return null;
    }
}
