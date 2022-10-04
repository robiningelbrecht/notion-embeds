<?php

namespace App\Controller;

use App\ValueObject\Notion\NotionSalaryDatabaseId;
use Notion\Databases\Query;
use Notion\Notion;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class CostIncomeSummaryController
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
            Query::create()
                ->withAddedSort(Query\Sort::property("Month")
                    ->descending())
                ->withPageSize(1),
        );

        $pages = $result->pages();
        /** @var \Notion\Pages\Page $page */
        $page = reset($pages);

        /** @var \Notion\Pages\Properties\PropertyInterface[] $properties */
        $properties = $page->properties();

        $response->getBody()->write($this->twig->render('cost-income-summary.html.twig', [
            'net_salary' => (string)$properties['Net salary']->number(),
        ]));
        return $response;
    }
}