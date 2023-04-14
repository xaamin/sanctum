<p align="center"><img src="/art/logo.svg" alt="Logo Laravel Sanctum"></p>

<p align="center">
    <a href="https://github.com/xaamin/lumen-sanctum/actions"><img src="https://github.com/xaamin/lumen-sanctum/workflows/tests/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/xaamin/lumen-sanctum"><img src="https://img.shields.io/packagist/dt/xaamin/lumen-sanctum" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/xaamin/lumen-sanctum"><img src="https://img.shields.io/packagist/v/xaamin/lumen-sanctum" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/xaamin/lumen-sanctum"><img src="https://img.shields.io/packagist/l/xaamin/lumen-sanctum" alt="License"></a>
</p>

## Introduction

Lumen Sanctum provides a featherweight authentication system for SPAs and simple APIs.

## Installation

This package requires requires php >= 8.0 and lumen >= 9

Step 1 - Install the package on your project
```
composer require xaamin/lumen-sanctum
```

Step 2 - Add the service provider in bootstrap/app.php
```
$app->register(
    Laravel\Sanctum\SanctumServiceProvider::class
);
```

Step 3 - Use `sanctum` as your driver for `api` guard in your `config/auth.php` file, copy the auth config file sample from [here](https://raw.githubusercontent.com/xaamin/lumen-sanctum/main/config/sanctum.php).
```
'guards' => [
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
        'hash' => false,
    ],
],
```

## Official Documentation

Documentation for Sanctum can be found on the [Laravel website](https://laravel.com/docs/sanctum).

## Contributing

Thank you for considering contributing to Sanctum! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/xaamin/lumen-sanctum/security/policy) on how to report security vulnerabilities.

## License

Lumen Sanctum is open-sourced software licensed under the [MIT license](LICENSE.md).
