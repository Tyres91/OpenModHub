<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function createApplication(): Application
    {
        $connection = env('DB_CONNECTION');

        if ($connection !== 'sqlite') {
            throw new \RuntimeException(
                "Test safety check failed: DB_CONNECTION is '{$connection}' instead of 'sqlite'. "
                .'Tests must run against SQLite in-memory to protect the real database. '
                .'Ensure phpunit.xml sets DB_CONNECTION=sqlite or .env.testing exists.'
            );
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
