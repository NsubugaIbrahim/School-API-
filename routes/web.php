<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'School Management API',
        'docs' => '/api/* — see routes/api.php',
    ]);
});
