<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class Prod03PageDescriptionEscapingTest extends TestCase
{
    #[DataProvider('pageDescriptionViews')]
    public function test_client_name_is_escaped_once_in_page_description(string $viewPath, string $clientNameExpression): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/'.$viewPath);
        $expectedExpression = "@section('page-description', ".$clientNameExpression.')';

        $this->assertNotFalse($view);
        $this->assertSame(1, substr_count($view, $expectedExpression));
    }

    public static function pageDescriptionViews(): array
    {
        return [
            'brief form' => [
                'prod03/briefs/form.blade.php',
                "'Client Brief ' . \$clientBrief->request_number . ' · ' . e(\$clientBrief->client_name)",
            ],
            'brief detail' => [
                'prod03/briefs/show.blade.php',
                "\$brief->clientBrief->request_number . ' · ' . e(\$brief->clientBrief->client_name)",
            ],
        ];
    }
}
