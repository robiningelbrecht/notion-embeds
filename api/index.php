<?php

use App\Controller\SalaryEvolutionChartController;
use App\Controller\CostIncomeSummaryController;
use App\Controller\InvestmentsChartController;
use App\ValueObject\Notion\NotionSalaryDatabaseId;
use App\ValueObject\Notion\NotionMonthlyExpensesDatabaseId;
use App\ValueObject\Notion\NotionInvestmentsDatabaseId;
use Notion\Notion;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
use Slim\Psr7\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/../vendor/autoload.php';

// Load env file.
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Build DI config.
$builder = new DI\ContainerBuilder();
$builder->addDefinitions([
    FilesystemLoader::class => DI\create(FilesystemLoader::class)->constructor(dirname(__DIR__) . '/templates'),
    Environment::class => DI\create(Environment::class)->constructor(DI\get(FilesystemLoader::class)),
    Notion::class => Notion::create($_ENV['NOTION_API_SECRET']),
    NotionSalaryDatabaseId::class => NotionSalaryDatabaseId::fromString($_ENV['NOTION_DATABASE_SALARY']),
    NotionMonthlyExpensesDatabaseId::class => NotionMonthlyExpensesDatabaseId::fromString($_ENV['NOTION_DATABASE_MONTHLY_EXPENSES']),
    NotionInvestmentsDatabaseId::class => NotionInvestmentsDatabaseId::fromString($_ENV['NOTION_DATABASE_INVESTMENTS']),
]);

// Create app.
AppFactory::setContainer($builder->build());
$app = AppFactory::create();

// Configure routes.
$app->get('/salary', SalaryEvolutionChartController::class . ':handle');
$app->get('/cost-income-summary', CostIncomeSummaryController::class . ':handle');
$app->get('/investment-allocation', InvestmentsChartController::class . ':handleAllocationChart');

// Add route middleware to ensure APP_SECRET is included in request.
$app->add(function (Request $request, RequestHandlerInterface $requestHandler) {
    $forbiddenResponse = new Response();
    $forbiddenResponse->getBody()->write('YOU HAVE NO POWER HERE');
    $forbiddenResponse->withStatus(400);

    if (empty($request->getQueryParams()['APP_SECRET'])) {
        return $forbiddenResponse;
    }

    if ($request->getQueryParams()['APP_SECRET'] != $_ENV['APP_SECRET']) {
        return $forbiddenResponse;
    }

    return $requestHandler->handle($request);
});

$app->run();