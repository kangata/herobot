<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing transactions before starting tests
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    protected function tearDown(): void
    {
        // Clean up any remaining transactions after tests
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        
        parent::tearDown();
    }
}
