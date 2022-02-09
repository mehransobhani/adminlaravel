<?php

use App\Http\Controllers\AcademyController;
use App\Http\Controllers\ArtController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeliveryCancelController;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeliveryStatusController;
use App\Models\Service;
use App\Models\ServiceCity;
use App\Models\ServicePlan;
use Symfony\Component\CssSelector\Node\FunctionNode;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\QueryController;
use App\Http\Middleware\ApiAdminAuthenticationMiddleware;
use App\Http\Middleware\ApiAuthenticationMiddleware;
use App\Models\Discount;
use App\Models\Province;
use App\Models\SP;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/get-cities', [DeliveryController::class, 'getCities']);
Route::get('/get-delivery-services/{id}', function($id){
    $availableServices = ServiceCity::where('city_id', $id)->get();

    $serviceIds = array();
    $serviceNames = array();

    if($availableServices->isNotEmpty()){
        foreach($availableServices as $availableService){
            array_push($serviceIds, $availableService->service_id);
            array_push($serviceNames, $availableService->service->name);
            $response = array(
                "servcieIds" => $serviceIds,
                "serviceNames" => $serviceNames,
            );
            echo json_encode($response);
        }
    }else{
        $response = array(
            "servcieIds" => $serviceIds,
            "serviceNames" => $serviceNames,
        );
        echo json_encode($response);
    }
});
Route::post('/get-delivery-status',                                                 [DeliveryController::class, 'getDeliveryStatus']);
Route::post('/cancel-delivery',                                                     [DeliveryController::class, 'cancelDelivery']);
Route::post('/add-delivery',                                                        [DeliveryController::class, 'addDelivery']);
Route::post('/get-service-and-price',                                               [DeliveryController::class, 'getServiceAndPrice']);

Route::middleware([ApiAdminAuthenticationMiddleware::class])->group(function () {

    /***| DISCOUNT ROUTES |***/
    Route::get('/all-discounts',                                                    [DiscountController::class, 'getAllDiscounts']);
    Route::post('/discount/filtered-paginated-discounts',                           [DiscountController::class, 'filteredPaginatedDiscounts']); // TO BE TESTED!
    Route::get('/all-provinces',                                                    [DiscountController::class, 'getAllProvinces']);
    Route::post('/search-discount-relevant-dependencies',                           [DiscountController::class, 'searchRelevantDependencies']);
    Route::post('/check-cart-products-discount',                                    [DiscountController::class, 'checkCartProductsDiscount']); //done
    Route::post('/check-products-discount',                                         [DiscountController::class, 'checkProductsDiscount']); //done
    Route::post('/discount/no-paginated-filter-category-products',                  [DiscountController::class, 'noPaginatedFilterCategoryProducts']); // TO BE TESTED!
    Route::post('/discount/add-products-to-multi-product-discount',                 [DiscountController::class, 'addProductstoMultiProductDiscount']); // DONE!
    Route::post('/discount/edit-multi-product-discount-general-information',        [DiscountController::class, 'editMultiProductDiscountGeneralInformation']); // OK!
    Route::post('/discount/multi-product-discount-products',                        [DiscountController::class, 'multiProductDiscountProducts']); // OK!
    Route::post('/discount/edit-multi-product-discount-product-information',        [DiscountController::class, 'editMultiProductDiscountProductInformation']); // OK!
    Route::post('/discount/remove-product-from-multi-product-discount',             [DiscountController::class, 'removeProductFromMultiProductDiscount']); // TO BE TESTED!

    Route::post('/add-dependency-to-discount',                                      [DiscountController::class, 'addDependencyToDiscount']); //done
    Route::post('/remove-dependency-from-discount',                                 [DiscountController::class, 'removeDependencyFromDiscount']); //done
    Route::post('/remove-discount',                                                 [DiscountController::class, 'removeDiscount']); //change remove discount to disable discount or its better to have toggle discount
    Route::post('/edit-discount',                                                   [DiscountController::class, 'editDiscount']); //this is the last thing that I will implement
    Route::post('/check-discount-code',                                             [DiscountController::class, 'checkDiscountCode']); //done
    Route::post('/check-cart-discount',                                             [DiscountController::class, 'checkCartDiscount']);
    Route::post('/filtered-paginated-discount-reports',                             [DiscountController::class, 'filteredPaginatedDiscountReports']); // TO BE TESTED!
    Route::post('/discount/add-discount',                                           [DiscountController::class, 'addInitialDiscount']);//adds a new discount with just getting discount type as an input...
    Route::post('/discount-information',                                            [DiscountController::class, 'discountInformation']);
    Route::post('/discount-relevant-dependencies',                                  [DiscountController::class, 'getDiscountRelevantDependencies']);
    Route::post('/discounts-basic-information',                                     [DiscountController::class, 'getDiscountsBasicInformation']);
    Route::post('/toggle-discount',                                                 [DiscountController::class, 'toggleDiscount']);
    Route::get('/testing', function(){
       echo 'hello react from laravel';
    });
    Route::get('/get-products-buy-logs',                                            [QueryController::class, 'getProductStockBuyLogs']);
    Route::get('/get-products-sell-logs',                                           [QueryController::class, 'getProductStockSellLogs']);
    Route::get('/users-bought-course',                                              [QueryController::class, 'getUsersWhoBoughtCourse']);
    Route::get('/users-dont-order',                                                 [QueryController::class, 'getUsersWhoDontOrder']);
    Route::get('/users-come-but-not-buy',                                           [QueryController::class, 'getUsersWhoComeButDidntBuy']);
    Route::get('/custom-query',                                                     [QueryController::class, 'customQuery']);

    Route::post('/category/filtered-paginated-category-course',                      [CategoryController::class, 'filteredPaginatedCategoryCourse']);

    /***| CATEGORY ROUTES |***/
    Route::get('/active-categories',                                                [CategoryController::class, 'getActiveCategories']); // TO BE TESTED!
    Route::get('/category/unlinked-root-categories',                                [CategoryController::class, 'unlinkedRootCategories']); // OK!
    Route::get('/category/filtered-paginated-linked-categories',                    [CategoryController::class, 'filteredPaginatedLinkedCategories']); // TO BE TESTED!
    Route::post('/category/add-category-course-link',                               [CategoryController::class, 'addCategoryCourseLink']); // OK!

    /***| ART ROUTES |***/
    Route::get('/art/active-arts',                                                  [ArtController::class,  'activeArts']);
    Route::get('/art/unlinked-arts',                                                [ArtController::class,  'unlinkedArts']);
    Route::post('/art/filtered-paginated-linked-arts',                              [ArtController::class,  'filteredPaginatedLinkedArts']);
    Route::post('/art/add-art-course-link',                                         [ArtController::class,  'addArtCourseLink']);
    Route::post('/art/remove-course-link',                                          [ArtController::class,  'removeCourseLink']);
    Route::post('/art/remove-art-links',                                            [ArtController::class,  'removeArtLinks']);
    Route::post('/art/arts-linked-courses',                                         [ArtController::class,  'artsLinkedCourses']);
    Route::post('/art/add-course-to-art',                                           [ArtController::class,  'addCourseToArt']);

    /***| ACADEMY ROUTES |***/
    Route::get('/academy/all-courses',                                              [AcademyController::class,  'allCourses']); // OK!

    Route::get('/test-date', function(){
        echo date('Y-m-d H:i:s', 1438320376);
    });
});
