<?php

use App\Models\City;
use App\Models\Service;
use App\Models\ServiceCity;
use App\Models\ServicePlan;
use App\Models\SP;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    //return view('welcome');
	echo "Hello World from admin page laravel.";
});

Route::get('/test', function(){
    //return view('form-test');
     echo 'testing by the way...';       
});
