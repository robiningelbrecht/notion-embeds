<?php

namespace App\Controller;

use App\ValueObject\Notion\NotionSalaryDatabaseId;
use Notion\Databases\Query;
use Notion\Notion;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class SalaryEvolutionChartController
{
    public function __construct(
        private readonly Notion $notion,
        private readonly NotionSalaryDatabaseId $notionSalaryDatabaseId,
        private readonly Environment $twig
    )
    {
    }

    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $database = $this->notion->databases()->find($this->notionSalaryDatabaseId);
        $result = $this->notion->databases()->query(
            $database,
            Query::create()->withAddedSort(Query\Sort::property("Month")->ascending()),
        );

        /** @var \Notion\Pages\Page $page */
        $labels = $data = [];
        foreach ($result->pages() as $page) {
            /** @var \Notion\Pages\Properties\PropertyInterface[] $properties */
            $properties = $page->properties();
            $labels[] = $properties['Month']->start()->format('F Y');

            $data['gross'][] = $properties['Gross salary']->number();
            $data['net'][] = $properties['Net salary']->number();
        }

        $datasets = [
            [
                'borderColor' => '#36a2eb',
                'label' => 'Gross',
                'data' => '[' . implode(',', $data['gross']) . ']',
                'hidden' => 'true',
            ],
            [
                'borderColor' => '#ffce56',
                'label' => 'Net',
                'data' => '[' . implode(',', $data['net']) . ']',
                'hidden' => 'false',
            ],
        ];

        $response->getBody()->write($this->twig->render('salary-chart.html.twig', [
            'labels' => '[' . implode(',', array_map(fn(string $label) => sprintf('"%s"', $label), $labels)) . ']',
            'datasets' => $datasets,
        ]));

        return $response;
    }
}