<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/metrics', function () {
    return response("
# HELP app_status Application status
# TYPE app_status gauge
app_status 1

# HELP memory_usage Memory usage in bytes
# TYPE memory_usage gauge
memory_usage " . memory_get_usage()
    , 200)
    ->header('Content-Type', 'text/plain; version=0.0.4');
});
