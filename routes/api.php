<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Models\User;
use App\Models\Activity;
use App\Models\Part;

use App\Http\Controllers\Api\IndicatorsController;
use App\Http\Controllers\Api\TimingsController;
use App\Http\Controllers\Api\UsersController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->append('can');
    });

    Route::get('/stats', function (Request $request) {
        return [
            'clients' => User::where('roles', 'like', '%client%')->count(),
            'activities' => Activity::whereNotNull('confirmed_at')->count(),
            'activity_types' => Part::where('type', 0)->withCount('activities')->get()
        ];
    });

    Route::post('logout', 'Api\Auth\LoginController@logout');

    Route::resource('projects', 'Api\ProjectsController');
    Route::patch('users/merge', [UsersController::class, 'merge']);
    Route::get('users/{user}/client-stats', [UsersController::class, 'clientStats']);
    Route::resource('users', 'Api\UsersController');
    Route::resource('forms', 'Api\FormsController');
    Route::resource('locations', 'Api\LocationsController');

    Route::put('activities/{activity}/start', 'Api\ActivitiesController@start');
    Route::put('activities/{activity}/stop', 'Api\ActivitiesController@stop');
    Route::resource('activities', 'Api\ActivitiesController');

    Route::get('timings/total', 'Api\TimingsController@total');
    Route::get('timings/aggregated', 'Api\TimingsController@aggregated');
    Route::post('timings/{timing}', [TimingsController::class, 'duplicate']);
    Route::put('timings/verify', [TimingsController::class, 'batchVerify']);
    Route::patch('timings/freeze', [TimingsController::class, 'freeze']);
    Route::resource('timings', 'Api\TimingsController');
    Route::resource('requests', 'Api\RequestsController');

    Route::get('parts', 'Api\PartsController@index');
    Route::get('services', 'Api\ServicesController@index');
    Route::get('positions', 'Api\PositionsController@index');
    Route::get('indicators', [IndicatorsController::class, 'index']);

    Route::post('calls', function (Request $request) {
        $n1 = $request->user()->phone;
        $n2 = $request->phone;

        if ($n1 && $n2) {
            $n1 = substr(str_replace(' ', '', $n1), 4, 9);
            $n2 = substr(str_replace(' ', '', $request->phone), 4, 9);

            $res = file_get_contents("http://callapi.intilish.uz/call.php?n1=$n1&n2=$n2");

            if ($res != 'FALSE') {
                return [];
            }
        }

        return ['error' => true];
    });
});

Route::middleware('guest:sanctum')->group(function () {
    Route::post('login', 'Api\Auth\LoginController@login');
    // Route::post('register', 'Api\RegisterController@register');
    // Route::post('password/challenge', 'Api\ForgotPasswordController@sendChallenge');
    // Route::post('password/reset', 'Api\ResetPasswordController@reset');
});
