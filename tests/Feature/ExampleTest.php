<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ExampleTest extends TestCase
{
    public function test_home_page_is_available(): void
    {
        $response = $this->get('/');

        $response->assertSuccessful();
    }

    public function test_health_endpoint_is_available(): void
    {
        $response = $this->get('/up');

        $response->assertSuccessful();
    }
}
