<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
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
            
        });
        $this->renderable(function (Throwable $e) {
            dd($e);
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'message' => 'Page not found',
                ], 404);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return redirect()->route('login::page::');
            }
            // dd($e);
        });
    }
}
