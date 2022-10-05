<?php

namespace App\Controller;

use App\MoneyFormatter;
use App\ValueObject\Notion\NotionInvestmentsDatabaseId;
use App\ValueObject\Notion\NotionMonthlyExpensesDatabaseId;
use App\ValueObject\Notion\NotionSalaryDatabaseId;
use Brick\Money\Money;
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
        private readonly NotionMonthlyExpensesDatabaseId $notionMonthlyExpensesDatabaseId,
        private readonly NotionInvestmentsDatabaseId $notionInvestmentsDatabaseId,
        private readonly Environment $twig
    )
    {
    }

    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $netSalary = $this->getLatestSalaryFromNotion();
        $rentLize = Money::of(195, 'EUR');
        $totalExpenses = $this->getTotalExpensesFromNotion();

        $response->getBody()->write($this->twig->render('cost-income-summary.html.twig', [
            'total_invested' => MoneyFormatter::format($this->getTotalInvestedFromNotion()),
            'net_salary' => MoneyFormatter::format($netSalary),
            'rent_lize' => MoneyFormatter::format($rentLize),
            'total_income' => MoneyFormatter::format($netSalary->plus($rentLize)),
            'total_expenses' => MoneyFormatter::format($totalExpenses),
            'total_after_expenses' => MoneyFormatter::format($netSalary->plus($rentLize)->minus($totalExpenses)),
        ]));
        return $response;
    }

    private function getLatestSalaryFromNotion(): Money
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
        /** @var \Notion\Pages\Properties\PropertyInterface[] $properties */
        $properties = reset($pages)->properties();
        return Money::of($properties['Net salary']->number(), 'EUR');
    }

    private function getTotalExpensesFromNotion(): Money
    {
        $totalExpenses = Money::of(0, 'EUR');
        $database = $this->notion->databases()->find($this->notionMonthlyExpensesDatabaseId);

        $result = $this->notion->databases()->query(
            $database,
            Query::create()
        );

        foreach ($result->pages() as $page) {
            /** @var \Notion\Pages\Properties\PropertyInterface[] $properties */
            $properties = $page->properties();
            $totalExpenses = $totalExpenses->plus(Money::of($properties['Expense']->number(), 'EUR'));
        }

        return $totalExpenses;
    }

    public function getTotalInvestedFromNotion(): Money
    {
        $totalInvested = Money::of(0, 'EUR');
        $database = $this->notion->databases()->find($this->notionInvestmentsDatabaseId);

        $result = $this->notion->databases()->query(
            $database,
            Query::create()
        );

        foreach ($result->pages() as $page) {
            /** @var \Notion\Pages\Properties\PropertyInterface[] $properties */
            $properties = $page->properties();
            $totalInvested = $totalInvested->plus(Money::of($properties['EUR invested']->number(), 'EUR'));
        }

        return $totalInvested;
    }
}