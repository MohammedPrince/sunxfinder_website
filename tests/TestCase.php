<?php

namespace Tests;

use Database\Seeders\DefaultDataSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase builds the test database from database/schema/mysql-schema.sql, which
     * has the tables but not the default rows some migrations insert: seed those once.
     */
    protected bool $seed = true;

    protected string $seeder = DefaultDataSeeder::class;
}
