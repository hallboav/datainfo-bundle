<?php

namespace Hallboav\DatainfoBundle\Sistema\Client\Middleware;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class JsonResponse extends Response
{
    /**
     * @var array
     */
    protected $json;

    /**
     * Construtor.
     *
     * @param array       $json
     * @param integer     $status
     * @param array       $headers
     * @param string|null $body
     * @param string      $version
     * @param string|null $reason
     */
    public function __construct(array $json, int $status = 200, array $headers = [], ?string $body = null, string $version = '1.1', ?string $reason = null)
    {
        parent::__construct($status, $headers, $body, $version, $reason);

        $this->json = $json;
    }

    /**
     * Obtém resposta JSON.
     *
     * @return array
     */
    public function getJson(): array
    {
        return $this->json;
    }

    /**
     * Método usado no Middleware::mapResponse para retornar JsonResponse em caso de resposta em formato JSON.
     *
     * @param ResponseInterface $response
     *
     * @return void
     *
     * @throws \UnexpectedValueException Quando há algum erro no JSON.
     */
    public static function parse(ResponseInterface $response): ResponseInterface
    {
        if (false !== strpos($response->getHeaderLine('Content-Type'), 'application/json')) {
            $data = json_decode($response->getBody()->getContents(), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \UnexpectedValueException(json_last_error_msg());
            }

            return new self(
                $data,
                $response->getStatusCode(),
                $response->getHeaders(),
                $response->getBody(),
                $response->getProtocolVersion(),
                $response->getReasonPhrase()
            );
        }

        return $response;
    }
}
