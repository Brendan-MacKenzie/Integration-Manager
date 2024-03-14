<?php

use Illuminate\Support\Facades\Route;

use BrendanMacKenzie\IntegrationManager\Http\Controllers\AuthorizationController;

Route::group(['namespace' => 'BrendanMacKenzie\IntegrationManager\Http\Controllers'], function() {
    Route::get('/integration/{id}/authorization', [AuthorizationController::class, 'authorize'])->where(['id' => '[0-9]+']);
});