<?php

namespace Laravel\Sanctum\Tests;

use Mockery;
use Laravel\Sanctum\Guard;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\SanctumGuard;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Events\Authenticated;
use Laravel\Sanctum\SanctumServiceProvider;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;

class SanctumGuardTest extends TestCase
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

    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function testAuthenticationForInvalidCredentialsFails()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);
        $sanctum = new Guard($factory);
        $provider = new EloquentUserProvider(app('hash')->driver('bcrypt'), SanctumGuardUser::class);

        Event::fake([
            Authenticated::class,
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $guard = new SanctumGuard($sanctum, $request, $provider);

        SanctumGuardUser::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ]);

        $guard = new SanctumGuard($sanctum, $request, $provider);

        $validated = $guard->validate([
            'email' => 'taylor@laravel.com',
            'password' => 'test',
        ]);

        $this->assertFalse($validated);

        Event::assertNotDispatched(Authenticated::class);
    }

    public function testAuthenticationForValidCredentialsSucceeds()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);
        $sanctum = new Guard($factory);
        $provider = new EloquentUserProvider(app('hash')->driver('bcrypt'), SanctumGuardUser::class);

        Event::fake([
            Authenticated::class,
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $guard = new SanctumGuard($sanctum, $request, $provider);

        SanctumGuardUser::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ]);

        $guard = new SanctumGuard($sanctum, $request, $provider);

        $validated = $guard->validate([
            'email' => 'taylor@laravel.com',
            'password' => 'password',
        ]);

        $this->assertTrue($validated);

        Event::assertDispatched(Authenticated::class);
    }

    public function testSetUserDispatchesAuthenticationEvent()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);
        $sanctum = new Guard($factory);
        $provider = new EloquentUserProvider(app('hash')->driver('bcrypt'), SanctumGuardUser::class);

        Event::fake([
            Authenticated::class,
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $guard = new SanctumGuard($sanctum, $request, $provider);

        $user = SanctumGuardUser::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ]);

        $guard = new SanctumGuard($sanctum, $request, $provider);

        $guard->setUser($user);

        Event::assertDispatched(Authenticated::class);
    }

    protected function getPackageProviders($app)
    {
        return [SanctumServiceProvider::class];
    }
}

class SanctumGuardUser extends Authenticatable implements HasApiTokensContract
{
    use HasApiTokens;

    protected $table = 'users';
}
