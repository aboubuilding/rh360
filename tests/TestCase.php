<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Garde-fou : RefreshDatabase vide toutes les tables. On refuse de tourner
        // sur une base dont le nom ne se termine pas par « _test ».
        $base = config('database.connections.'.config('database.default').'.database');
        if (config('database.default') !== 'sqlite' && ! str_ends_with((string) $base, '_test')) {
            $this->fail("Tests interdits sur la base « {$base} » : utilisez une base dédiée suffixée _test.");
        }
    }
}
