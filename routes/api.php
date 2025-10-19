<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is a simple API route file. The file was missing which caused artisan
| commands to fail because Laravel attempts to require routes/api.php by
| default. This minimal file is safe for local development.
|
*/

Route::get('/ping', function () {
    return response()->json(['pong' => true]);
});
