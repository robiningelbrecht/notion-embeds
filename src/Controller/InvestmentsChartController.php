<?php

namespace App\Controller;

use App\ValueObject\Notion\NotionInvestmentsDatabaseId;
use Notion\Databases\Query;
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
        $database = $this->notion->databases()->find($this->notionInvestmentsDatabaseId);
        $result = $this->notion->databases()->query(
            $database,
            Query::create(),
        );

        $labels = [];
        $totalInvested = 0;
        $investedPerTicker = $this->calculatePercentagesPerTicker($result->pages(), $totalInvested, $labels);

        $datasets = [
            [
                'data' => '[' . implode(',', $investedPerTicker) . ']',
            ],
        ];
        $response->getBody()->write($this->twig->render('investment-pie-chart.html.twig', [
            'title' => 'Allocation',
            'labels' => '[' . implode(',', array_map(fn(string $label) => sprintf('"%s"', $label), $labels)) . ']',
            'datasets' => $datasets,
        ]));

        return $response;
    }

    public function handleIwdaVsEmimChart(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $database = $this->notion->databases()->find($this->notionInvestmentsDatabaseId);
        $result = $this->notion->databases()->query(
            $database,
            Query::create()->withFilter(Query\CompoundFilter::or(
                Query\SelectFilter::property('Ticker')->equals('IWDA'),
                Query\SelectFilter::property('Ticker')->equals('EMIM')
            )),
        );

        $labels = [];
        $totalInvested = 0;
        $investedPerTicker = $this->calculatePercentagesPerTicker($result->pages(), $totalInvested, $labels);

        $datasets = [
            [
                'data' => '[' . implode(',', $investedPerTicker) . ']',
            ],
        ];
        $response->getBody()->write($this->twig->render('investment-pie-chart.html.twig', [
            'title' => 'IWDA vs EMIM (88% - 12%)',
            'labels' => '[' . implode(',', array_map(fn(string $label) => sprintf('"%s"', $label), $labels)) . ']',
            'datasets' => $datasets,
        ]));

        return $response;
    }

    private function calculatePercentagesPerTicker(array $pages, int &$totalInvested, array &$labels): array
    {
        $investedPerTicker = [];
        foreach ($pages as $page) {
            /** @var \Notion\Pages\Properties\PropertyInterface[] $properties */
            $properties = $page->properties();
            $tickerId = $properties['Ticker']->id();
            $amountInvested = $properties['EUR invested']->number();

            $labels[$tickerId] = $properties['Ticker']->name();
            if (empty($investedPerTicker[$tickerId])) {
                $investedPerTicker[$tickerId] = 0;
            }
            $investedPerTicker[$tickerId] += $amountInvested;
            $totalInvested += $amountInvested;
        }

        // Calculate percentages and update labels.
        foreach ($investedPerTicker as $tickerId => $amountInvested) {
            $percentage = round(($amountInvested / $totalInvested) * 100, 2);
            $investedPerTicker[$tickerId] = $percentage;
            $labels[$tickerId] .= ' (' . $percentage . '%)';
        }

        return $investedPerTicker;
    }
}