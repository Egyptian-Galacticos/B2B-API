<?php

use App\Helpers\JWTResponseHelper;
use App\Http\Middleware\CheckProductOwnership;
use App\Http\Middleware\CustomJWTAuthentication;
use App\Http\Middleware\IsEmailVerified;
use App\Http\Middleware\IsSuspended;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'is_email_verified' => IsEmailVerified::class,
            'is_suspended'      => IsSuspended::class,
            'product.owner'     => CheckProductOwnership::class,
            'jwt.auth'          => CustomJWTAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $jwtHelper = new JWTResponseHelper;

        // Handle JWT authentication exceptions with custom responses
        $exceptions->render(function (TokenExpiredException $e, $request) use ($jwtHelper) {
            if ($request->is('api/*')) {
                return $jwtHelper->tokenExpired();
            }

            return null;
        });

        $exceptions->render(function (TokenInvalidException $e, $request) use ($jwtHelper) {
            if ($request->is('api/*')) {
                return $jwtHelper->tokenInvalid();
            }

            return null;
        });

        $exceptions->render(function (JWTException $e, $request) use ($jwtHelper) {
            if ($request->is('api/*')) {
                return $jwtHelper->tokenNotProvided();
            }

            return null;
        });

        $exceptions->render(function (UnauthorizedHttpException $e, $request) use ($jwtHelper) {
            if ($request->is('api/*')) {
                $message = $e->getMessage();

                // Handle specific JWT errors
                if (str_contains($message, 'Wrong number of segments') || str_contains($message, 'base64')) {
                    return $jwtHelper->tokenMalformed();
                }

                return $jwtHelper->authenticationRequired();
            }
        });
    })->create();
