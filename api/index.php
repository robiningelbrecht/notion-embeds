<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Notion\Notion;
use Notion\Databases\Query;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$twig = new Environment(new FilesystemLoader(dirname(__DIR__) . '/templates'));

$app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use ($twig) {
    $notion = Notion::create($_ENV['NOTION_API_SECRET']);

    $database = $notion->databases()->find($_ENV['NOTION_DATABASE_SALARY']);
    $result = $notion->databases()->query(
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

    $response->getBody()->write($twig->render('salary-chart.html.twig', [
        'labels' => '[' . implode(',', array_map(fn(string $label) => sprintf('"%s"', $label), $labels)) . ']',
        'datasets' => $datasets,
    ]));

    return $response;
});

$app->run();