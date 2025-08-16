<?php

namespace App\Exceptions;

use App\Traits\ApiResponseTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     */
    private function handleApiException(Request $request, Throwable $e)
    {
        // Authentication exceptions
        if ($e instanceof AuthenticationException) {
            return $this->unauthorizedResponse('Unauthenticated');
        }

        // Validation exceptions
        if ($e instanceof ValidationException) {
            return $this->errorResponse(
                'Validation error',
                422,
                $e->errors()
            );
        }

        // Model not found exceptions
        if ($e instanceof ModelNotFoundException) {
            return $this->notFoundResponse('Resource not found');
        }

        // Not found HTTP exceptions
        if ($e instanceof NotFoundHttpException) {
            return $this->notFoundResponse('Endpoint not found');
        }

        // HTTP exceptions
        if ($e instanceof HttpException) {
            return $this->errorResponse(
                $e->getMessage() ?: 'HTTP Error',
                $e->getStatusCode()
            );
        }

        // Database connection errors
        if ($e instanceof \PDOException || str_contains($e->getMessage(), 'database')) {
            return $this->serverErrorResponse(
                'Database connection error',
                config('app.debug') ? $e->getMessage() : null
            );
        }

        // Generic server errors
        return $this->serverErrorResponse(
            'An unexpected error occurred',
            config('app.debug') ? $e->getMessage() : null
        );
    }
}
