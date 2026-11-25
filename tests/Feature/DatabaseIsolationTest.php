<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    public function test_phpunit_uses_the_separate_test_database(): void
    {
        $this->assertSame(
            'rider_buddy_test',
            DB::connection()->getDatabaseName()
        );
    }
}
