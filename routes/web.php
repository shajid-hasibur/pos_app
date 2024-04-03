<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
    return view('admin.auth.login');
});

Route::get('/clear', function () {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('config:cache');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return 'Cleared!';
});
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');


Route::group(['namespace' => 'frontEnd'], function () {
    // Route::get('/','HomeController@home')->name('home');
    Route::get('/category/{id}', 'HomeController@shopcat')->name('shop.cat');
    Route::get('/product/{id}', 'HomeController@shoppro')->name('shop.pro');

    Route::get('/contact-us', 'HomeController@contactUs')->name('frontEnd.contactUs');
    Route::get('/service', 'HomeController@whereToBuy')->name('frontEnd.whereToBuy');
    Route::get('about', 'HomeController@showroom')->name('frontEnd.showroom');
    Route::get('premium-wax-technology', 'HomeController@premiumWaxTechnology')->name('frontEnd.premiumWaxTechnology');
    Route::get('/carnauba-wax-technology', 'HomeController@carnaubaWaxTechnology')->name('frontEnd.carnaubaWaxTechnology');

    Route::get('car-care-tips', 'HomeController@carCareTips')->name('frontEnd.carCareTips');
});



//web site category

Route::group(['middleware' => 'auth:admin', 'namespace' => 'admin', 'as' => 'admin.', 'prefix' => 'admin'], function () {

    Route::get('/website/category', 'WebcatController@index')->name('webcat');
    Route::get('/website/category/add', 'WebcatController@create')->name('webcat.create');
    Route::post('/website/category/store', 'WebcatController@store')->name('webcat.store');
    Route::post('/website/category/destroy/{id}', 'WebcatController@destroy')->name('webcat.destroy');
    Route::get('/website/category/edit/{slug}', 'WebcatController@edit')->name('webcat.edit');
    Route::post('/website/category/update/', 'WebcatController@update')->name('webcat.update');

    Route::get('/website/catagory-edit/{id}', 'WebcatController@editCatagory')->name('webcat.editCatagory');
    Route::put('/website/catagory-update', 'WebcatController@updateCatagory')->name('webcat.updateCatagory');
    Route::post('/website/catagory-delete', 'WebcatController@deleteCatagory')->name('webcat.deleteCatagory');

    Route::get('website/pruduct', 'WebproController@index')->name('webpro');
    Route::get('/website/pruduct/add', 'WebproController@create')->name('webpro.create');
    Route::post('/website/pruduct/store', 'WebproController@store')->name('webpro.store');
    Route::post('/website/pruduct/destroy/{id}', 'WebproController@destroy')->name('webpro.destroy');
    Route::get('/website/pruduct/edit/{slug}', 'WebproController@edit')->name('webpro.edit');
    Route::post('/website/pruduct/update/', 'WebproController@update')->name('webpro.update');

    Route::get('/website/product-view/{id}', 'WebproController@viewProduct')->name('webpro.viewProduct');
    Route::get('/website/product-edit/{id}', 'WebproController@editProduct')->name('webpro.editProduct');
    Route::put('/website/product-update', 'WebproController@updateProduct')->name('webpro.updateProduct');
    Route::post('/website/product-delete', 'WebproController@deleteProduct')->name('webpro.deleteProduct');

    Route::get('/attendence', 'HomeController@attendence')->name('attendence');
    Route::get('/attendence_list', 'HomeController@attendence_list')->name('attendence_list');
    Route::get('/attendence/data', 'HomeController@attendenceData')->name('attendenceData');
    Route::post('/add_attendence', 'HomeController@add_attendence')->name('add_attendence');

    Route::get('subscription/product/page', 'PaymentController@index')->name('subscription.product.view');
    Route::post('/saas/product/purchase', 'PaymentController@payment')->name('saas.product.payment');
    Route::post('saas/payment/success', 'PaymentController@success')->name('payment.success');

    Route::get('counting/days', 'SubscriptionController@decrementDays')->name('sub.decrementDays');
});

//store attendence

Route::post('/admin/store/attendence', 'AttendenceController@storeAttendence')->name('storeAttend');



// admin register
Route::get('/admin/register****brac****', 'Auth\RegisterController@showForm')->name('form');
Route::post('/admin/register/post', 'Auth\RegisterController@postForm')->name('postform');