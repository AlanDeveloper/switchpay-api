<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        api: __DIR__ . "/../routes/api.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\ForceJsonResponseMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            AuthenticationException $e,
            Request $request,
        ) {
            if ($request->is("api/*")) {
                return response()->json(["message" => "Unauthenticated"], 401);
            }
        });

        $exceptions->render(function (
            AuthorizationException $e,
            Request $request,
        ) {
            if ($request->is("api/*")) {
                return response()->json(
                    ["message" => "This action is unauthorized"],
                    403,
                );
            }
        });

        $exceptions->render(function (
            ModelNotFoundException $e,
            Request $request,
        ) {
            if ($request->is("api/*")) {
                return response()->json(
                    ["message" => "No query results for model"],
                    404,
                );
            }
        });

        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request,
        ) {
            if ($request->is("api/*")) {
                return response()->json(["message" => "Not found"], 404);
            }
        });

        $exceptions->render(function (
            MethodNotAllowedHttpException $e,
            Request $request,
        ) {
            if ($request->is("api/*")) {
                return response()->json(
                    ["message" => "Method not allowed"],
                    405,
                );
            }
        });

        $exceptions->render(function (
            ValidationException $e,
            Request $request,
        ) {
            if ($request->is("api/*")) {
                return response()->json(
                    [
                        "message" => "The given data was invalid",
                        "errors" => $e->errors(),
                    ],
                    422,
                );
            }
        });
    })
    ->create();
