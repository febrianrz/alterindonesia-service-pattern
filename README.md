# Laravel Service Pattern

This package provide a service pattern for Laravel application. This package use the default concept of MVC but with additional Service Layer.
We not change default logic of Laravel feature like Request, Resource, etc. Here the layer of this package:
1. Request comes from Route to Controller via FormRequest Laravel.
2. In controller, there is validation based on FormRequest instance.
3. After validation succeed, controller call Service Layer via Laravel Service Provider binding feature.
4. Service Layer will process the business logic and return the result to controller.
5. Controller will return the result to client via Resource.
6. Resource will format the result to client.

## Installation
```bash
composer require alterindonesia/service-pattern
```

## Usage
1. Update your Controller.php in app/Http/Controllers folder.
```php
<?php

namespace App\Http\Controllers;

use Alterindonesia\ServicePattern\Controllers\BaseController;

abstract class Controller extends BaseController
{

}
```

2. Your controller should be like:
```php
<?php

namespace App\Http\Controllers;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use App\Http\Requests\TestRequest;
use App\Http\Resources\TestResource;

class TestController extends Controller
{
    public function __construct(
        IServiceEloquent $service,
        string $request = TestRequest::class,
        string $response = TestResource::class
    ) {
        parent::__construct($service, $request, $response);
    }
}
```
you can change the request and response based on your need.

3. Create your service in app/Services folder.
4. Create Service via artisan command:
```bash
php artisan make:service TestService --model=Test
```
5. Your service should be like:
```php
<?php
namespace App\Services;
use Alterindonesia\ServicePattern\ServiceEloquents\BaseServiceEloquent;
use App\Models\Test;

class TestServiceEloquent extends BaseServiceEloquent
{
    public function __construct(
        Test $model
    ) {
        parent::__construct($model);
    }

}
```
6. It will auto generate for CRUD operation, you can override the method based on your need.
7. Your route will be like:
```php
<?php
use Illuminate\Support\Facades\Route;

Route::get('/test','App\Http\Controllers\TestController@index');
Route::post('/test','App\Http\Controllers\TestController@store');
Route::get('/test/{id}','App\Http\Controllers\TestController@show');
Route::put('/test/{id}','App\Http\Controllers\TestController@update');
Route::delete('/test/{id}','App\Http\Controllers\TestController@destroy');
```
8. Now, bind your service in AppServiceProvider.php
```php
<?php

namespace App\Providers;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use App\Http\Controllers\TestController;
use App\Services\TestServiceEloquent;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->when(TestController::class)
            ->needs(IServiceEloquent::class)
            ->give(TestServiceEloquent::class);

    }
}
```
9. Done, now you can use this package in your Laravel application.

### FAQ
- why you not use RepositoryLayer?
  - personally, I think Repository Layer is not necessary because Laravel Eloquent is already powerful.
  - but, you can create your own Repository Layer in Service Layer if you want.

### Next Feature
- [ ] Add Service Layer for Query Builder
- [ ] Add Yajra Datatables Service Layer
- [ ] Add Service Layer for Import/Export
- [ ] we think about it
