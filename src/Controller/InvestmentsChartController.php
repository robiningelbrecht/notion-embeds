<?php

namespace App\Controller;

use App\ValueObject\Notion\NotionInvestmentsDatabaseId;
use Notion\Notion;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class InvestmentsChartController
{
    public function __construct(
        private readonly Notion $notion,
        private readonly NotionInvestmentsDatabaseId $notionInvestmentsDatabaseId,
        private readonly Environment $twig
    )
    {

    }

    public function handleAllocationChart(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $labels = ['IWDA', 'EMIM', 'VWCE', 'ETH'];
        $datasets = [
            [
                'data' => '[' . implode(',', [91, 6, 2, 1]) . ']',
            ],
        ];
        $response->getBody()->write($this->twig->render('investment-allocation-chart.html.twig', [
            'labels' => '[' . implode(',', array_map(fn(string $label) => sprintf('"%s"', $label), $labels)) . ']',
            'datasets' => $datasets,
        ]));

        return $response;
    }
}