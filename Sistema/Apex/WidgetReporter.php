<?php

namespace Hallboav\DatainfoBundle\Sistema\Apex;

use GuzzleHttp\ClientInterface;
use Symfony\Component\DomCrawler\Crawler;
use Hallboav\DatainfoBundle\Sistema\Security\User\DatainfoUserInterface;
use Hallboav\DatainfoBundle\Sistema\Effort\FilteringEffortType;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class WidgetReporter
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Construtor.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Consulta um relatório.
     *
     * Retorna as informações em um array com até 9999 linhas.
     *
     * @param DatainfoUserInterface $user
     * @param \DateTimeInterface    $startDate
     * @param \DateTimeInterface    $endDate
     * @param FilteringEffortType   $effort    Tipo de esforço para filtro.
     * @param string                $instance
     * @param string                $ajaxId
     * @param string                $salt
     * @param string                $protected
     *
     * @return array Informações obtidas através da análise da resposta recebida.
     *
     * @throws \UnexpectedValueException Quando não é possível ler corretamente o conteúdo do elemento que possui a classe .apex_report_break.
     */
    public function report(DatainfoUserInterface $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate, FilteringEffortType $effort, string $instance, string $ajaxId, string $salt, string $protected): array
    {
        $parameters = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '10',
            'p_instance' => $instance,
            'p_request' => sprintf('PLUGIN=%s', urlencode($ajaxId)),
            'p_widget_action' => 'paginate',
            'p_pg_min_row' => '1',
            'p_pg_max_rows' => '9999',
            'p_pg_rows_fetched' => '9999',
            'x01' => '88237305110178876',
            'p_json' => json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P10_COD_USER',      'v' => strtoupper($user->getDatainfoUsername())],
                        ['n' => 'P10_W_DAT_INICIO',  'v' => $startDate->format('d/m/Y')],
                        ['n' => 'P10_W_DAT_TERMINO', 'v' => $endDate->format('d/m/Y')],
                        ['n' => 'P10_W_TIP_ESFORCO', 'v' => $effort->getId()],
                    ],
                    'protected' => $protected,
                ],
            ]),
        ];

        $response = $this->client->post('/apex/wwv_flow.ajax', [
            'form_params' => $parameters,
        ]);

        $crawler = new Crawler(
            $response->getBody()->getContents(),
            sprintf('%s%s', $this->client->getConfig('base_uri'), '/apex/wwv_flow.ajax')
        );

        if (1 === $crawler->filter('.nodatafound')->count()) {
            return [];
        }

        $data = [];
        $dataIndex = 0;
        $keyMap = [
            'description',
            'project',
            'activity',
            'start_time',
            'end_time',
            'worked_time',
            'effort_type',
        ];

        $crawler->filter('.report-standard > tr')->each(function (Crawler $tr, int $i) use (&$data, &$date, &$keyMap, &$dataIndex) {
            // Existem três tipos de <tr>'s
            // 1. .apex_report_break: contém a data e o total de horas trabalhadas
            //                        <tr><td colspan="9" id="DAT_ESFORCO" class="apex_report_break">Data do Realizado: 05/01/2018 - Total de horas: 8:11</td></tr>

            // 2. <tr> sem atributo:  não utilizado; contém cabeçalhos visuais
            //                        <tr><th align="left" id="DES_ESFORCO_000" class="header">Descrição</th> <th>...</th> </tr>

            // 3. .highlight-row:     contém os valores que foram postados pelo usuário
            //                        <tr class="highlight-row"><td headers="DES_ESFORCO_000" class="data">foo bar</td> <td>...</td> </tr>

            // Obtém todos os nós filhos do <tr> que tenham a classe .apex_report_break
            $break = $tr->children()->filter('.apex_report_break');

            // Se o <tr> atual da iteração conter informações do realizado
            if (1 === $break->count()) {
                // ... então faz parse do texto "Data do Realizado: 05/01/2018 - Total de horas: 8:11"
                $html = $break->first()->html();

                if (!preg_match('#(?P<current_date>\d{2}\/\d{2}\/\d{4}).*\ (?P<worked_time>\d{1,2}\:\d{2})#', $html, $matches)) {
                    throw new \UnexpectedValueException(sprintf('preg_match failed to parse contents of apex_report_break class.'));
                }

                $date = \DateTime::createFromFormat('!d/m/Y', $matches['current_date'], new \DateTimeZone('America/Sao_Paulo'));

                $data[$dataIndex] = [
                    'date_formatted' => $date->format('d/m/Y'),
                    'worked_time' => $matches['worked_time'],
                ];

                $dataIndex++;
            } else if ('highlight-row' === $tr->attr('class')) {
                // ... então vamos tratar os valores postados pelo usuário

                $details = [];
                $tr->filter('.data')->each(function (Crawler $data, $i) use (&$details, &$keyMap) {
                    $html = trim($data->html());

                    // Um caracter vazio que parece um caracter de espaço que simboliza que
                    // a descrição é igual a anterior
                    if (0 === $i && "\xc2\xa0" === $html) {
                        $html = null;
                    }

                    $details[$keyMap[$i]] = $html;
                });

                $data[$dataIndex - 1]['details'][] = $details;
            }
        });

        return $data;
    }
}
