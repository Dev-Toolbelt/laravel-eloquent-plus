<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class IntegrationTestCase extends OrchestraTestCase
{
    use DatabaseTransactions;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', TestUser::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTablesIfNotExists();
    }

    protected function createTestTablesIfNotExists(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        $this->createTestTables();
    }

    protected function createTestTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 36)->unique()->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 36)->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('document')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('quantity')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->date('birth_date')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->text('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('simple_models', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('external_id_models', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 36)->unique()->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 36)->unique()->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
