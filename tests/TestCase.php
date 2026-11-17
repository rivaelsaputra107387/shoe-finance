<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // RefreshDatabase mengosongkan database, jadi tes hanya boleh berjalan di database khusus tes.
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database !== 'finlog_test') {
            throw new RuntimeException("Tes dibatalkan: database '{$database}' bukan database tes.");
        }

        return $app;
    }
}
