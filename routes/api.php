<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::post('create-account', [\App\Http\Controllers\AuthController::class,'createAccount']);
Route::post('login', [\App\Http\Controllers\AuthController::class,'login']);
Route::post('verify-otp', [\App\Http\Controllers\AuthController::class,'verifyOTP']);
Route::post('resend-otp', [\App\Http\Controllers\AuthController::class,'resendOTP']);
Route::post('reset-password', [\App\Http\Controllers\AuthController::class,'resetPassword']);
//get services
Route::get('services', [\App\Http\Controllers\ServiceController::class,'index']);
//get counties
Route::get('counties', [\App\Http\Controllers\CountyController::class,'index']);
//get subcounties
Route::post('subcounties', [\App\Http\Controllers\SubcountyController::class,'index']);
//create-new adress
Route::post('create-b-address', [\App\Http\Controllers\AddressController::class,'store']);
//create-new adress
Route::post('create-r-address', [\App\Http\Controllers\ResidentialAddressController::class,'store']);
//search to claim
Route::get('search-to-claim/{location_id}', [\App\Http\Controllers\UniversalAddressController::class,'search_to_claim']);
//to claim
Route::post('claim-address', [\App\Http\Controllers\UniversalAddressController::class,'claim_address']);

//to unclaim.action invoked from my-addresses page
Route::post('unclaim-address', [\App\Http\Controllers\UniversalAddressController::class,'unclaim_address']);
//list addreses in homepage
Route::get('home', [\App\Http\Controllers\UniversalAddressController::class,'home']);
//view single address
Route::get('view/{address_id}', [\App\Http\Controllers\UniversalAddressController::class,'view']);
//like unlike share
Route::post('action', [\App\Http\Controllers\UniversalAddressController::class,'action']);
//my addresses
Route::get('my-addresses', [\App\Http\Controllers\UniversalAddressController::class,'my_addresses']);

//return last location id
Route::get('last-id', [\App\Http\Controllers\AddressController::class,'generateLocationID']);
Route::group([

    'middleware' => 'auth',
 //   'prefix' => 'auth'

], function ($router) {

    
    Route::post('logout', [\App\Http\Controllers\AuthController::class,'logout']);
    Route::post('refresh', [\App\Http\Controllers\AuthController::class,'refresh']);
    Route::post('me', [\App\Http\Controllers\AuthController::class,'me']);
    
});
