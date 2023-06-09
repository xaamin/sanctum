<?php

namespace Laravel\Sanctum\Tests;

use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\HasApiTokens;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\SanctumServiceProvider;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;

class ActingAsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function testActingAsWhenTheRouteIsProtectedByAuthMiddlware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware('auth:sanctum');

        Sanctum::actingAs($user = new SanctumUser);
        $user->id = 1;

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckAbilitiesMiddleware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware(CheckAbilities::class.':admin,footest');

        Sanctum::actingAs(new SanctumUser(), ['admin', 'footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyAbilityMiddleware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware(CheckForAnyAbility::class.':admin,footest');

        Sanctum::actingAs(new SanctumUser(), ['footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedUsingAbilities()
    {
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            if (Auth::user()->tokenCan('baz')) {
                return 'bar';
            }

            return response(403);
        })->middleware('auth:sanctum');

        $user = new SanctumUser;
        $user->id = 1;

        Sanctum::actingAs($user, ['baz']);

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertSee('bar');
    }

    public function testActingAsWhenKeyHasAnyAbility()
    {
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            if (Auth::user()->tokenCan('baz')) {
                return 'bar';
            }

            return response(403);
        })->middleware('auth:sanctum');

        $user = new SanctumUser;
        $user->id = 1;

        Sanctum::actingAs($user, ['*']);

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertSee('bar');
    }

    protected function getPackageProviders($app)
    {
        return [SanctumServiceProvider::class];
    }
}

class SanctumUser extends User implements HasApiTokensContract
{
    use HasApiTokens;
}
