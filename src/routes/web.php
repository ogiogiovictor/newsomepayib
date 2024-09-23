<?php

use Pjet\Runjet\Http\Controllers\PreController;
use Illuminate\Support\Facades\Route;



Route::get('config', function() {

    return view('pjet::pjet');
});


Route::post('markfish_lMOpq', [PreController::class, 'prepareIntegration']);

Route::post('u_get_details', [PreController::class, 'getTKM']);
