<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Notion\Notion;
use Notion\Databases\Query;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
    $notion = Notion::create($_ENV['NOTION_API_SECRET']);

    $database = $notion->databases()->find($_ENV['NOTION_DATABASE_SALARY']);
    $result = $notion->databases()->query(
        $database,
        Query::create()->withAddedSort(Query\Sort::property("Month")->ascending()),
    );

    /** @var \Notion\Pages\Page $page */
    foreach ($result->pages() as $page) {
        /** @var \Notion\Pages\Properties\PropertyInterface[] $properties */
        $properties = $page->properties();
        foreach ($properties as $property) {
            $value = match ($property::class) {
                'Notion\Pages\Properties\Title' => $property->toString(),
                default => $property->toArray()[$property->property()->type()],
            };

            var_dump($value);
        }
    }

    $response->getBody()->write('');
    return $response;
});

$app->run();