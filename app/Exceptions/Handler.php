<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Arr;
use PhpParser\Node\Expr\Throw_;
use Sentry\State\Scope;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];


    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        //handle 404 for api calls
        if ($exception instanceof ModelNotFoundException && $request->wantsJson()) {
            return response()->json(['message' => 'Not Found!'], 404);
        }

        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'The form has expired due to inactivity. Please try again');
        }

        if ($exception instanceof ThrottleRequestsException) {
            $error = ['Transaction is processing, please try again after 60 seconds'];
            return response()->json(['success' => false, 'message' => 'Transaction is processing, please try again after 60 seconds', 'data' => ['error' => $error]], 429);
        }

        if ($exception instanceof AuthenticationException) {
            $error = ['Unauthenticated'];
            return response()->json(['success' => false, 'message' => 'Unauthenticated', 'data' => ['error' => $error]], 401);
        }

        if ($exception && !config('app.debug')) {
            return response()->json([
                'success' => false,
                'message' => 'Oops something went wrong, Please try again',
                'data'=>['error'=>['Oops something went wrong, Please try again']]
            ],500);
        }

        return parent::render($request, $exception);
    }


    /**
     * Convert an authentication exception into a response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Auth\AuthenticationException $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'data' => ['error' => [$exception->getMessage()]]],
            401);


    }
}
