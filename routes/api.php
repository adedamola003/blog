<?php


use App\Http\Controllers\Api\V1\Auth\NewPasswordController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Content\ArticleController;
use App\Http\Controllers\Api\V1\Content\PublisherController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


//real user application Apis
Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1'], function () {
    //Authentication Routes
    Route::post('auth/register', [RegisteredUserController::class, 'store'])->middleware('guest');
    Route::post('auth/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
    Route::post('auth/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');
    Route::post('auth/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest');
    Route::post('auth/reset-password', [NewPasswordController::class, 'store'])->middleware('guest');

    Route::middleware(['auth:sanctum'])->group(function () {
        //Publishers Routes
        Route::get('publisher/list_tags', [PublisherController::class, 'getTags']);
        Route::post('publisher/create', [PublisherController::class, 'publishArticle']);


    });

    //Article routes
    Route::get('articles', [ArticleController::class, 'getArticles']);
    Route::get('article/{id}', [ArticleController::class, 'getArticle']);
    Route::post('article/{id}/comment', [ArticleController::class, 'postComment']);
    Route::post('article/{id}/like', [ArticleController::class, 'likeArticle']);
        Route::fallback(function () {
        return response()->json(['message' => 'Page Not Found'], 404);
    });
});
