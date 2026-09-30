<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Les tests de fonctionnalité s'exécutent sur le TestCase Laravel avec une
| base SQLite en mémoire réinitialisée à chaque test (RefreshDatabase).
| Chaque fichier appelle $this->seed() dans son beforeEach.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');
