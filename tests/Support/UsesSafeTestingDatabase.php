<?php

namespace Tests\Support;

trait UsesSafeTestingDatabase
{
    protected function assertUsingSafeTestingDatabase(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (!app()->environment('testing')) {
            $this->fail('Integration tests must run with APP_ENV=testing.');
        }

        if ($connection === 'sqlite' && ($database === ':memory:' || str_contains($database, 'testing'))) {
            return;
        }

        if (!str_contains($database, 'testing')) {
            $this->fail(sprintf(
                'Integration tests are blocked because DB_DATABASE is "%s". Use a copied testing database such as "pkl_testing".',
                $database
            ));
        }
    }
}
