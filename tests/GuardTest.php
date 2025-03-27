<?php

namespace Laravel\Sanctum\Tests;

use Mockery;
use DateTime;
use stdClass;
use DateTimeInterface;
use Laravel\Sanctum\Guard;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\HasApiTokens;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Auth\EloquentUserProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;

class GuardTest extends TestCase
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

    public function testAuthenticationIsNotAttemptedWithWebMiddleware()
    {
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null, 'users');

        $factory->shouldNotReceive('guard')
            ->with('web');

        $guard->__invoke(Request::create('/', 'GET'));
    }

    public function testAuthenticationIsAttemptedWithTokenIfNoSessionPresent()
    {
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null, 'users');

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = $guard->__invoke($request);

        $this->assertNull($user);
    }

    public function testAuthenticationWithTokenFailsIfExpired()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, 1, 'users');

        $webGuard = Mockery::mock(stdClass::class);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
            'created_at' => new DateTime('-60 minutes'),
        ]);

        $user = $guard->__invoke($request);

        $this->assertNull($user);
    }

    public function testAuthenticationWithTokenFailsIfExpiresAtHasPassed()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null, 'users');

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
            'expires_at' => new DateTime('-60 minutes'),
        ]);

        $user = $guard->__invoke($request);

        $this->assertNull($user);
    }

    public function testAuthenticationWithTokenSucceedsIfExpiresAtNotPassed()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null, 'users');

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
            'expires_at' => now()->addMinutes(60),
        ]);

        $user = $guard->__invoke($request);

        $this->assertNull($user);
    }

    public function testAuthenticationIsSuccessfulWithTokenIfNoSessionPresent()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        $returnedUser = $guard->__invoke($request);

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertEquals($token->id, $returnedUser->currentAccessToken()->id);
        $this->assertInstanceOf(DateTimeInterface::class, $returnedUser->currentAccessToken()->last_used_at);
    }

    public function testAuthenticationIsSuccessfulWithTokenIfNoSessionPresentWithoutTouchingLastUsedAt()
    {
        $trackUsage = config('sanctum.track_usage');

        config(['sanctum.track_usage' => false]);

        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        $returnedUser = $guard->__invoke($request);

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertEquals($token->id, $returnedUser->currentAccessToken()->id);
        $this->assertNull($returnedUser->currentAccessToken()->last_used_at);

        config(['sanctum.track_usage' => $trackUsage]);
    }

    public function testAuthenticationWithTokenFailsIfUserProviderIsInvalid()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['auth.guards.sanctum.provider' => 'users']);
        config(['auth.providers.users.model' => 'App\Models\User']);

        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        Event::fake([
            TokenAuthenticated::class,
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        $returnedUser = $requestGuard->setRequest($request)->user();

        $this->assertNull($returnedUser);
        $this->assertInstanceOf(EloquentUserProvider::class, $requestGuard->getProvider());

        Event::assertNotDispatched(TokenAuthenticated::class);
    }

    /**
     * @dataProvider invalidTokenDataProvider
     */
    public function testAuthenticationWithTokenFailsIfTokenHasInvalidFormat($invalidToken)
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null, 'users');

        $request = Request::create('/', 'GET');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
            'expires_at' => now()->subMinutes(60),
        ]);

        $request->headers->set('Authorization', $invalidToken);
        $returnedUser = $guard->__invoke($request);

        $this->assertNull($returnedUser);
    }

    public function testAuthenticationIsSuccessfulWithTokenIfUserProviderIsValid()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['auth.guards.sanctum.provider' => 'users']);
        config(['auth.providers.users.model' => User::class]);

        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        Event::fake([
            TokenAuthenticated::class,
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        $returnedUser = $requestGuard->setRequest($request)->user();

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertInstanceOf(EloquentUserProvider::class, $requestGuard->getProvider());

        Event::assertDispatched(TokenAuthenticated::class);
    }

    public function testAuthenticationFailsIfCallbackReturnsFalse()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['auth.guards.sanctum.provider' => 'users']);
        config(['auth.providers.users.model' => User::class]);

        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        Sanctum::authenticateAccessTokensUsing(function ($accessToken, bool $isValid) {
            $this->assertInstanceOf(PersonalAccessToken::class, $accessToken);
            $this->assertTrue($isValid);

            return false;
        });

        $user = $requestGuard->setRequest($request)->user();

        $this->assertNull($user);

        Sanctum::$accessTokenAuthenticationCallback = null;
    }

    public function testAuthenticationIsSuccessfulWithTokenInCustomHeader()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Auth-Token', 'test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        Sanctum::getAccessTokenFromRequestUsing(function (Request $request) {
            return $request->header('X-Auth-Token');
        });

        $returnedUser = $guard->__invoke($request);

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertEquals($token->id, $returnedUser->currentAccessToken()->id);
        $this->assertInstanceOf(DateTimeInterface::class, $returnedUser->currentAccessToken()->last_used_at);

        Sanctum::$accessTokenRetrievalCallback = null;
    }

    public function testAuthenticationFailsWithTokenInAuthorizationHeaderWhenUsingCustomHeader()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        Sanctum::getAccessTokenFromRequestUsing(function (Request $request) {
            return $request->header('X-Auth-Token');
        });

        $returnedUser = $guard->__invoke($request);

        $this->assertNull($returnedUser);

        Sanctum::$accessTokenRetrievalCallback = null;
    }

    public function testAuthenticationFailsWithTokenInCustomHeaderWhenUsingDefaultAuthorizationHeader()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Auth-Token', 'test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        $returnedUser = $guard->__invoke($request);

        $this->assertNull($returnedUser);
    }

    protected function getPackageProviders($app)
    {
        return [SanctumServiceProvider::class];
    }

    public function invalidTokenDataProvider(): array
    {
        return [
            [''],
            ['|'],
            ['test'],
            ['|test'],
            ['1ABC|test'],
            ['1ABC|'],
            ['1,2|test'],
            ['Bearer'],
            ['Bearer |test'],
            ['Bearer 1,2|test'],
            ['Bearer 1ABC|test'],
            ['Bearer 1ABC|'],
        ];
    }
}

class User extends Model implements HasApiTokensContract
{
    use HasApiTokens;
}
