<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discount;
use App\Models\DiscountDependency;
use App\Models\DiscountLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Province;
use Exception;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class DiscountController extends Controller
{
    public function addDiscount(Request $request){}

    public function checkCartProductsDiscount(Request $request){

        if(isset($request->productsInformation) && isset($request->userId) && isset($request->provinceId)){
            $productsInformationObject = json_decode($request->productsInformation);
            $userId = $request->userId;
            $provinceId = $request->provinceId;
            
            $neworder = 1;

            $orders = Order::where('user_id', $userId);
            if($orders->count() !== 0){
                $neworder = 0;
            }
            if(gettype($productsInformationObject->productIds) !== 'array' || gettype($productsInformationObject->categoryIds) !== 'array' || gettype($productsInformationObject->productPrices) !== 'array'){
                echo json_encode(array('status' => 'failed', 'message' => 'parameters are set but with wrong format'));
                die();
            }

            function checkUserOrderBetweenDatee($user, $start, $finish){
                if($start !== null && $finish !== null){
                    $orders = Order::where([['user_id', '=', $user], ['date', '>=', $start], ['date', '<=', $finish]]);
                    if($orders->count() !== 0){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }

            $allDiscounts = Discount::where('status', 1)->where(function($query){
                return $query->where('type_id', 1)->orWhere('type_id', 2);
            })->where(function($query){
                return $query->where('expiration_date', null)->orWhere([['expiration_date', '>=', time()]]);
            })->where(function($query) use ($neworder){
                return $query->where('neworder', 0)->orWhere([['neworder', '=', 1], ['neworder', '=', $neworder]]);
            })->where(function($query){
                return $query->where('numbers_left', null)->orWhere([['numbers_left', '>', 0]]);
            });
            if($allDiscounts->count() > 0){
                $allDiscounts = $allDiscounts->get();
                $responseArray = array();
                for($i=0; $i < count($productsInformationObject->productIds); $i++){
                    $reducedPrice = 0;
                    foreach($allDiscounts as $discount){
                        if(($discount->start_date === null && $discount->finish_date === null) || ($discount->start_date !== null && $discount->finish_date !== null && !checkUserOrderBetweenDatee($userId, $discount->start_date, $discount->finish_date))){
                            $search = false;
                            if($discount->type_id === 1 && ($discount->products->count() === 0 || $discount->products->where('dependency_id', $productsInformationObject->productIds[$i])->count() !== 0)){
                                $search = true;
                            }else if($discount->type_id === 2 && ($discount->categories->count() === 0 || $discount->categories->where('dependency_id', $productsInformationObject->categoryIds[$i])->count() !== 0)){
                                $search = true;
                            }
                            if($search){
                                $userFound = false;
                                $provinceFound = false;
                                if($discount->users->count() === 0){
                                    $userFound = false;
                                }else if($discount->users->where('dependency_id', $userId)->count() !== 0){
                                    $userFound = true;
                                }
                                if($discount->provinces->count() === 0){
                                    $provinceFound = false;
                                }else if($discount->provinces->where('dependency_id', $provinceId)->count() !== 0){
                                    $provinceFound = true;
                                }
                                if(($discount->users->count() === 0 && $discount->provinces->count() === 0) ||
                                ($discount->users->count() === 0 && $provinceFound) ||
                                ($discount->provinces->count() === 0 && $userFound) ||
                                ($userFound && $provinceFound)){
                                    $userUsedThisDiscount = false;
                                    $usedDiscounts = DiscountLog::where('user_id', $userId)->where('discount_id', $discount->id);
                                    if($usedDiscounts->count() !== 0){
                                        $userUsedThisDiscount = true;
                                    }
                                    if($discount->reusable === 1 || ($discount->reusable === 0 && !$userUsedThisDiscount)){
                                        if($discount->min_price === null || $discount->min_price <= $productsInformationObject->productPrices[$i]){
                                            if($discount->price !== null){
                                                $reducedPrice += $discount->price;
                                            }
                                            if($discount->percent !== null){
                                                $reducedPrice = ($discount->percent / 100) * $productsInformationObject->productPrices[$i];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if($reducedPrice === 0){
                        array_push($responseArray, array('percent' => 0, 'price' => $productsInformationObject->productPrices[$i]));
                    }else{
                        if($reducedPrice < $productsInformationObject->productPrices[$i]){
                            array_push($responseArray, array('percent' => (integer)(($reducedPrice/$productsInformationObject->productPrices[$i]) * 100), 'price' => 100 * (integer)(($productsInformationObject->productPrices[$i] - $reducedPrice) / 100)));
                        }else{
                            array_push($responseArray, array('percent' => 100, 'price' => 0));
                        }
                    }
                }
            }else{
                foreach($productsInformationObject->productPrices as $productPrice){
                    array_push($responseArray, array('percent' => 0, 'price' => $productPrice));
                }
            }
            echo json_encode($responseArray);
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function checkProductsDiscount(Request $request){
        if(isset($request->productsInformation)){
            $productsInformationObject = json_decode($request->productsInformation);
            if(gettype($productsInformationObject->productIds) !== 'array' || gettype($productsInformationObject->categoryIds) !== 'array' || gettype($productsInformationObject->productPrices) !== 'array'){
                echo json_encode(array('status' => 'failed', 'message' => 'parameters are set but with wrong format'));
                die();
            }
            $responseArray = array();
            $allDiscounts = Discount::where(function($query){
                return $query->where('type_id', 1)->orWhere('type_id', 2);
            })->where(function($query){
                return $query->where('expiration_date', null)->orWhere([['expiration_date', '>=', time()]]);
            })->where(function($query){
                return $query->where('numbers_left', null)->orWhere([['numbers_left', '>', 0]]);
            })->where('neworder', 0)->where('reusable', 1)->where('start_date', null)->where('finish_date', null)->where('status', 1);
            if($allDiscounts->count() > 0){
                $allDiscounts = $allDiscounts->get();
                for($i=0; $i<count($productsInformationObject->productIds); $i++){
                    $reducedPrice = 0;
                    foreach($allDiscounts as $discount){
                        if($discount->users->count() === 0 && $discount->provinces->count() === 0){
                            if($discount->min_price === null || $discount->min_price <= $productsInformationObject->productPrices[$i]){
                                if(($discount->type_id === 1 && ($discount->products->count() === 0 || $discount->products->where('dependency_id', $productsInformationObject->productIds[$i])->count() > 0)) ||
                                ($discount->type === 2 && ($discount->categories->count() === 0 || $discount->categories->where('dependency_id', $$productsInformationObject->categoryIds[$i])->count() > 0))){
                                    if($discount->price !== null){
                                        $reducedPrice += $discount->price;
                                    }
                                    if($discount->percent !== null){
                                        $reducedPrice += ($discount->percent / 100) * $productPricesArray[$i];
                                    }
                                    echo $discount->id;
                                }
                            }
                        }
                    }
                    if($reducedPrice !== 0){
                        if($reducedPrice < $productsInformationObject->productPrices[$i]){
                            array_push($responseArray, array('percent' => (integer)(($reducedPrice/$productsInformationObject->productPrices[$i]) * 100), 'price' => 100 * (integer)(($productsInformationObject->productPrices[$i] - $reducedPrice) / 100)));
                        }else{
                            array_push($responseArray, array('percent' => 100, 'price' => 0));
                        }
                    }else{
                        array_push($responseArray, array('percent' => 0, 'price' => $productsInformationObject->productPrices[$i]));
                    }
                }
            }else{
                foreach($productsInformationObject->productPrices as $productPrice){
                    array_push($responseArray, array('percent'=> 0, 'price' => $productPrice));
                }
            }
            echo json_encode($responseArray);
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function addDependencyToDiscount(Request $request){
        if(isset($request->discountId) && isset($request->dependencyId) && isset($request->dependencyType)){
            $discountId = $request->discountId;
            $dependencyType = $request->dependencyType;
            $dependencyId = $request->dependencyId;
            $discount = Discount::where('id', $discountId);
            if($discount->count() !== 0){
                $discount = $discount->first();
                if($dependencyType === 'user'){
                    $user = User::where('username', $dependencyId);
                    if($user->count() !== 0){
                        $user = $user->first();
                        $userId = $user->id;
                        $dependency = DiscountDependency::where('discount_id', $discountId)->where('type_id', 4)->where('dependency_id', $userId);
                        if($dependency->count() === 0){
                            $discountDependency = new DiscountDependency();
                            $discountDependency->type_id = 4;
                            $discountDependency->discount_id = $discountId;
                            $discountDependency->dependency_id = $userId;
                            if($discountDependency->save()){
                                $dependency = DiscountDependency::where('type_id', 4)->where('dependency_id', $userId)->first();
                                echo json_encode(array('status' => 'done', 'message' => 'dependency added successfully', 'id' => $dependency->id, 'username' => $user->username, 'name' => $user->name));
                            }else{
                                echo json_encode(array('status' => 'failed', 'an error occured while adding a new user dependency'));
                            }
                        }else{
                            echo json_encode(array('status' => 'failed', 'message' => 'dependency exists'));
                        }
                    }else{
                        echo json_encode(array('status' => 'failed', 'message' => 'dependency not found'));
                    }
                }else if($dependencyType === 'province'){
                    $province = Province::where('id', $dependencyId);
                    if($province->count() !== 0){
                        $province = $province->first();
                        $dependency = DiscountDependency::where('discount_id', $discountId)->where('type_id', 3)->where('dependency_id', $dependencyId);
                        if($dependency->count() === 0){
                            $discountDependency = new DiscountDependency();
                            $discountDependency->type_id = 3;
                            $discountDependency->discount_id = $discountId;
                            $discountDependency->dependency_id = $dependencyId;
                            if($discountDependency->save()){
                                $dependency = DiscountDependency::where('type_id', 3)->where('dependency_id', $dependencyId)->first();
                                echo json_encode(array('status' => 'done', 'message' => 'dependency added successfully', 'id' => $dependency->id, 'name' => $province->name));
                            }else{
                                echo json_encode(array('status' => 'failed', 'an error occured while adding a new province dependency'));
                            }
                        }else{
                            echo json_encode(array('status' => 'failed', 'message' => 'dependency exists'));
                        }
                    }else{
                        echo json_encode(array('status' => 'failed', 'message' => 'dependency not found'));
                    }
                }else if($dependencyType === 'product'){
                    $product = Product::where('id', $dependencyId);
                    if($product->count() !== 0){
                        $product = $product->first();
                        $dependency = DiscountDependency::where('discount_id', $discountId)->where('type_id', 1)->where('dependency_id', $dependencyId);
                        if($dependency->count() === 0){
                            $discountDependency = new DiscountDependency();
                            $discountDependency->type_id = 1;
                            $discountDependency->discount_id = $discountId;
                            $discountDependency->dependency_id = $dependencyId;
                            if($discountDependency->save()){
                                $dependency = DiscountDependency::where('type_id', 1)->where('dependency_id', $dependencyId)->first();
                                echo json_encode(array('status' => 'done', 'message' => 'dependency added successfully', 'id' => $dependency->id, 'name' => $product->prodName_fa));
                            }else{
                                echo json_encode(array('status' => 'failed', 'an error occured while adding a new product dependency'));
                            }
                        }else{
                            echo json_encode(array('status' => 'failed', 'message' => 'dependency exists'));
                        }
                    }else{
                        echo json_encode(array('status' => 'failed', 'message' => 'dependency not found'));
                    }
                }else if($dependencyType === 'category'){
                    $category = Category::where('id', $dependencyId);
                    if($category->count() !== 0){
                        $category = $category->first();
                        $dependency = DiscountDependency::where('discount_id', $discountId)->where('type_id', 2)->where('dependency_id', $dependencyId);
                        if($dependency->count() === 0){
                            $discountDependency = new DiscountDependency();
                            $discountDependency->type_id = 2;
                            $discountDependency->discount_id = $discountId;
                            $discountDependency->dependency_id = $dependencyId;
                            if($discountDependency->save()){
                                $dependency = DiscountDependency::where('type_id', 2)->where('dependency_id', $dependencyId)->first();
                                echo json_encode(array('status' => 'done', 'message' => 'dependency added successfully', 'id' => $dependency->id, 'name' => $category->name));
                            }else{
                                echo json_encode(array('status' => 'failed', 'an error occured while adding a new product dependency'));
                            }
                        }else{
                            echo json_encode(array('status' => 'failed', 'message' => 'dependency exists'));
                        }
                    }else{
                        echo json_encode(array('status' => 'failed', 'message' => 'dependency not found'));
                    }
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function removeDependencyFromDiscount(Request $request){
        if(isset($request->dependencyId)){
            $dependencyId = $request->dependencyId;
            $discountDependency = DiscountDependency::where('id', $dependencyId);
            if($discountDependency->count() === 1){
                $discountDependency = $discountDependency->first();
                if($discountDependency->delete()){
                    echo json_encode(array('status' => 'done', 'message' => 'dependency succussfully removed'));
                }else{
                    echo json_encode(array('status' => 'failed', 'message' => 'an error occured while removing the dependency'));
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'dependency not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    /*public function addProductToDiscount(Request $request){
        if(isset($request->discountId) && isset($request->productId)){
            $discountId = $request->discountId;
            $productId = $request->productId;
            $discount = Discount::where('id', $discountId);
            if($discount->count() !== 0){
                $discount = $discount->first();
                $discountProductsString = $discount->products;
                if($discountProductsString === null){
                    $discount->products = '[' . $productId . ']';
                    if($discount->save()){
                        echo json_encode(array('status' => 'done', 'message' => 'product added successfully'));
                    }else{
                        echo json_encode(array('status' => 'failed', 'message' => 'an error occured while updating the discount'));
                    }
                }else{
                    $discountProductsArray = json_decode($discountProductsString, true);
                    if(!in_array($productId, $discountProductsArray)){
                        array_push($discountProductsArray, (integer)$productId);
                        $discount->products = json_encode($discountProductsArray);
                        if($discount->save()){
                            echo json_encode(array('status' => 'done', 'message' => 'product added successfully'));
                        }else{
                            echo json_encode((array('status' => 'failed', 'message' => 'an error occured while updating the discount')));
                        }
                    }else{
                        echo json_encode(array('status' => 'failed', 'message' => 'product already exists'));
                    }
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function removeProductFromDiscount(Request $request){
        if(isset($request->discountId) && isset($request->productId)){
            $discountId = $request->discountId;
            $productId = $request->productId;
            $discount = Discount::where('id', $discountId);
            if($discount->count() !== 0){
                $discount = $discount->first();
                $discountProductsString = $discount->products;
                if($discountProductsString === null){
                    echo json_encode(array('status' => 'failed', 'message' => 'product not found'));
                }else{
                    $discountProductsArray = json_decode($discountProductsString, true);
                    $productPosition = array_search($productId, $discountProductsArray);
                    if($productPosition !== false){
                        if(count($discountProductsArray) === 1){
                            $discount->products = null;
                        }else{
                            unset($discountProductsArray[$productPosition]);
                            $discount->products = json_encode(array_values($discountProductsArray));
                        }
                        if($discount->save()){
                            echo json_encode(array('status' => 'done', 'message' => 'product successfully removed from discount'));
                        }else{
                            echo json_encode(array('status' => 'failed', 'message' => 'an error occured while updating the discount'));
                        }
                    }else{
                        echo json_encode(array('status' => 'failed', 'message' => 'product not found'));
                    }
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }*/

    public function addNewDiscount(Request $request){
        if(!isset($request->type) || !isset($request->title)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough information', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $time = time();
        $discountType = $request->type;
        $discountTitle = $request->title;
        $discount = new Discount();
        $discount->title = $discountTitle;
        $discount->type = $discountType;
        $discount->date = $time;
        $queryResult = DB::insert(
            "INSERT INTO discounts (
                title, `type`, `date`
            ) VALUES (
                '$discountTitle', '$discountType', $time 
            )"
        );
        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'sql', 'message' => 'query erro while inserting a new discount', 'umessage' => 'خطا هنگام ذخیره اطلاعات جدید'));
            exit();
        }
        echo json_encode(array('status' => 'done', 'message' => 'new discount successfully added'));
    }

    public function removeDiscount(Request $request){
        if(isset($request->discountId)){
            $discountId = $request->discountId;
            $discount = Discount::where('id', $discountId);
            if($discount->count() !== 0){
                $discount = $discount->first();
                if($discount->delete()){
                    echo json_encode((array('status' => 'done', 'message' => 'discount successfully deleted')));
                }else{
                    echo json_encode(array('status' => 'failed', 'message' => 'an error occured while deleting the discount'));
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function editDiscount(Request $request){
        
        /*if(isset($request->discountId) && isset($request->title) && isset($request->description) && isset($request->status) && isset($request->expirationDate)
            && isset($request->percent) && isset($request->price) && isset($request->minPrice) && isset($request->maxPrice) && isset($neworder) && isset($request->numbersLeft)
            && isset($request->joinable) && isset($request->startDate) && isset($request->finishDate) ){*/
            $discountId = $request->discountId;
            $title = $request->title;
            $description = $request->description;
            $status = $request->status;
            $expirationDate = $request->expirationDate;
            $percent= $request->percent;
            $price = $request->price;
            $minPrice = $request->minPrice;
            $neworder = $request->neworder;
            $numbersLeft = $request->numbersLeft;
            $joinable = $request->joinable;
            $startDate = $request->startDate;
            $finishDate = $request->finishDate;
            $userStartDate = $request->userStartDate;
            $userFinishDate = $request->userFinishDate;
            $code = $request->code;
            $maxPrice = $request->maxPrice;
            
            /*if($expirationDate == 0){
                $expirationDate = null;
            }
            if($percent == 0){
                $percent = null;
            }
            if($price == 0){
                $price = null;
            }
            if($minPrice = 0){
                $minPrice = null;
            }
            if($numbersLeft == -1){
                $numbersLeft = null;
            }
            if($startDate == 0){
                $startDate = null;
            }
            if($finishDate = 0){
                $finishDate = null;
            }
            if($type === 'order' || $type === 'shipping'){
                if(!isset($request->code) || !isset($request->maxPrice)){
                    echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter. code or maxPrice are missing'));
                    die();        
                }else{
                    $code = $request->code;
                    $maxPrice = $request->maxPrice;
                }
            }
            if($code == 0){
                $code = null;
            }
            if($maxPrice == 0){
                $maxPrice = 0;
            }

            if($code !== null){
                $allDiscountsWithSameCode = Discount::where('code', $code);
                if($allDiscountsWithSameCode->count() !== 0){
                    echo json_encode(array('status' => 'failed', 'message' => 'there is a discount with the same code. try another code'));
                    die();
                }
            }
            */

            $discount = Discount::where('id', $discountId);
            if($discount->count() !== 0){
                $discount = $discount->first();
                $discount->title = $title;
                $discount->description = $description;
                $discount->status = $status;
                $discount->expiration_date = $expirationDate;
                $discount->percent = $percent;
                $discount->price = $price;
                $discount->code = $code;
                $discount->min_price = $minPrice;
                $discount->max_price = $maxPrice;
                $discount->neworder = $neworder;
                $discount->numbers_left = $numbersLeft;
                $discount->start_date = $startDate;
                $discount->finish_date = $finishDate;
                $discount->user_start_date = $userStartDate;
                $discount->user_finish_date = $userFinishDate;
                $discount->joinable = $joinable;
                $discount->date = time();
                if($discount->save()){
                    echo json_encode(array('status' => 'done', 'message' => 'discounted successfully updated'));
                }else{
                    echo json_encode(array('status' => 'filed', 'message' => 'an error occured while updating the discount'));
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'discount not found'));
            }
        /*}else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter', 'desc' => $request->description));
        }*/
    }

    /*public function shippingDiscountedPrice(Request $request){
        if(isset($request->price) && isset($request->deliveryPrice)){
            $price = $request->price;
            $deliveryPrice = $request->deliveryPrice;
            $minus = 0;
            $shippingDiscounts = Discount::where([['type', '=', 'shipping'], ['status', '=', 1], ['expiration_date', '=', null]])->orWhere([['type', '=', 'shipping'], ['status', '=', 1], ['expiration_date', '>=', time()]]);
            if($shippingDiscounts->count() !== 0){
                $shippingDiscounts = $shippingDiscounts->get();
                $newcomer = 1;
                $lastPurchase = 1627215507;
                foreach($shippingDiscounts as $shippingDiscount){
                    $minPrice = 0;
                    if($shippingDiscount->newcomer !== $newcomer){
                        continue;
                    }
                    if($shippingDiscount->start_date !== null && $shippingDiscount->finish_date !== null && ($shippingDiscount->start_date > $lastPurchase || $lastPurchase > $shippingDiscount->finish_date)){
                        continue;
                    }
                    if($shippingDiscount->min_price !== null){
                        $minPrice = $shippingDiscount->min_price;
                    }
                    if($price >= $minPrice){
                        if($shippingDiscount->price !== null){
                            $minus += $shippingDiscount->price;
                        }
                        if($shippingDiscount->percent !== null){
                            $minus += ($shippingDiscount->percent/100) * $deliveryPrice;
                        }
                    }
                }
                if($deliveryPrice <= $minus){
                    echo json_encode(array('status' => 'done', 'deliveryPrice' => 0, 'message' => 'shipping discount successfully calculated'));
                }else{
                    $diff = $deliveryPrice - $minus;
                    $diff = (integer)($diff/100) * 100;
                    echo json_encode(array('status' => 'done', 'deliveryPrice' => $diff, 'message' => 'shipping discount successfully calculated'));
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }*/

    public function checkDiscountCode(Request $request){
        if(isset($request->productsInformation) && isset($request->discountCode) && isset($request->userId) && isset($request->provinceId) && isset($request->orderPrice) && isset($request->shippingPrice)){
            $productsInformationObject = json_decode($request->productsInformation);
            if(gettype($productsInformationObject->productIds) !== 'array' || gettype($productsInformationObject->categoryIds) !== 'array'){
                echo json_encode(array('status' => 'failed', 'discount' => false, 'message' => 'parameters are set but with wrong format'));
                die();
            }
            $userId = $request->userId;
            $provinceId = $request->provinceId;
            $discountCode = $request->discountCode;
            $orderPrice = $request->orderPrice;
            $shippingPrice = $request->shippingPrice;

            $newOrder = true;

            $userOrders = Order::where('user_id', $userId);
            if($userOrders->count() !== 0){
                $newOrder = false;
            }

            function checkUserOrderBetweenDates($user, $start, $finish){
                if($start !== null && $finish !== null){
                    $orders = Order::where([['user_id', '=', $user], ['date', '>=', $start], ['date', '<=', $finish]]);
                    if($orders->count() !== 0){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }
            
            $allDiscounts = Discount::where('code', $discountCode)->where('status', 1)->where(function ($query){
                return $query->where('expiration_date', null)->orWhere('expiration_date', '>', time());
            })->where(function ($query) use ($orderPrice){
                return $query->where('min_price', null)->orWhere('min_price', '<=', $orderPrice);
            });
            if($allDiscounts->count() !== 0){
                $discount = $allDiscounts->first();
                if($discount->neworder === 0 || ($discount->neworder === 1 && $newOrder)){
                    if($discount->numbers_left === null || $discount->numbers_left > 0){
                        $userUsedThisDiscount = false;
                        $usedDiscounts = DiscountLog::where('user_id', $userId)->where('discount_id', $discount->id);
                        if($usedDiscounts->count() !== 0){
                            $userUsedThisDiscount = true;
                        }
                        if($discount->reusable === 1 || ($discount->reusable === 0 && $userUsedThisDiscount === false)){
                            if(($discount->start_date === null && $discount->finish_date === null) ||
                               !checkUserOrderBetweenDates($userId, $discount->start_date, $discount->finish_date)
                            ){
                                $discountUserDependencies = $discount->users;
                                $discountProductDependencies = $discount->products;
                                $discountCategoryDependencies = $discount->categories;
                                $discountProvinceDependencies = $discount->provinces;
                                $dependencyPermission = true;
                                if($discountUserDependencies->count() !== 0){
                                    $found = false;
                                    foreach($discountUserDependencies as $userDependency){
                                        if($userDependency->dependency_id === (integer)$userId){
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if($found == false){
                                        $dependencyPermission = false;
                                    }
                                }
                                if($dependencyPermission && $discountProvinceDependencies->count() !== 0){
                                    $found = false;
                                    foreach($discountProvinceDependencies as $provinceDependency){
                                        if($provinceDependency->dependency_id === (integer)$provinceId){
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if($found == false){
                                        $dependencyPermission = false;
                                    }
                                }
                                if($dependencyPermission && $discountProductDependencies->count() !== 0){
                                    $found = false;
                                    foreach($discountProductDependencies as $productDependency){
                                        if(in_array($productDependency->dependency_id, $productsInformationObject->productIds)){
                                            $found = true;
                                            break;
                                        }
                                    }   
                                    if($found == false){
                                        $dependencyPermission = false;
                                    }    
                                }
                                if($dependencyPermission && $discountCategoryDependencies->count() !== 0){
                                    $found = false;
                                    foreach($discountCategoryDependencies as $categoryDependency){
                                        if(in_array($categoryDependency->dependency_id, $productsInformationObject->categoryIds)){
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if($found == false){
                                        $dependencyPermission = false;
                                    }
                                }
                                if($dependencyPermission){
                                    $reducedPrice = 0;
                                    $focusedPrice = $orderPrice;
                                    if($discount->type_id === 3){
                                        $focusedPrice = $shippingPrice;
                                    }
                                    if($discount->price !== null){
                                        $reducedPrice += $discount->price;
                                    }else if($discount->percent!== null){
                                        $reducedPrice += ($discount->percent / 100) * $focusedPrice;
                                    }
                                    if($discount->max_price !== null){
                                        if($reducedPrice > $discount->max_price){
                                            $reducedPrice = $discount->max_price;
                                        }
                                    }
                                    if($reducedPrice === 0){
                                        echo json_encode(array('status' => 'done', 'discount' => true, 'discountTypeId' => $discount->type_id, 'price' => 0, 'percent' => 0, 'joinable' => $discount->joinable));
                                    }else{
                                        echo json_encode(array('status' => 'done', 'discount' => true, 'discountTypeId' => $discount->type_id, 'price' => (integer)(($focusedPrice - $reducedPrice)/100) * 100, 'percent' => (integer)(($reducedPrice / $focusedPrice)*100), 'joinable' => $discount->joinable));
                                    }
                                }else{
                                    echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'does not have dependency permission'));
                                }
                            }else{
                                echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'user had order between start and finish date'));
                            }
                        }else{
                            echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'discount is not reusable'));
                        }
                    }else{  
                        echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'discount had limited number and now its finished'));
                    }
                }else{
                    echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'discount is for new users and you are not'));
                }             
            }else{
                echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function addInitialDiscount(Request $request){
        if(isset($request->typeId)){
            $typeId = $request->typeId;
            $time = time();
            $discount = new Discount();
            $discount->type_id = $typeId;
            $discount->date = $time;
            if($discount->save()){
                $discount = Discount::where('type_id', $typeId)->orderBy('id', 'DESC')->first();
                echo json_encode(array('status' => 'done', 'id' => $discount->id, 'typeId' => $typeId, 'message' => 'discount added successfully'));
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'an error occured while adding a new discount'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function discountInformation(Request $request){
        if(isset($request->id)){
            $discount = Discount::where('id', $request->id);
            if($discount->count() !== 0){
                $discount = $discount->first();
                echo $discount->toJson();
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'could not find the discount'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function checkCartDiscount(Request $request){ 
        if(isset($request->productsInformation) && isset($request->userId) && isset($request->provinceId) && isset($request->orderPrice) && isset($request->shippingPrice)){
            $productsInformationObject = json_decode($request->productsInformation); 
            if(gettype($productsInformationObject->productIds) !== 'array' || gettype($productsInformationObject->categoryIds) !== 'array'){ 
                echo json_encode(array('status' => 'failed', 'discount' => false, 'message' => 'parameters are set but with wrong format')); 
                die(); 
            } 
            $userId = $request->userId;
            $provinceId = $request->provinceId;
            $shippingPrice = $request->shippingPrice;
            $orderPrice = $request->orderPrice;
            
            $newOrder = true;

            $userOrders = Order::where('user_id', $userId);
            if($userOrders->count() !== 0){
                $newOrder = false;
            }
            
            $allDiscounts = Discount::where(function($query){
                return $query->where('type_id', 4)->orWhere('type_id', 3);
            })->where('code', null)->where('status', 1)->where(function($query){
                return $query->where('expiration_date', null)->orWhere('expiration_date', '>=', time());
            })->where(function($query){
                return $query->where('numbers_left', null)->orWhere('numbers_left', '>', 0);
            })->where(function($query) use ($newOrder){
                return $query->where('neworder', 0)->orWhere('neworder', $newOrder);
            })->where(function ($query) use ($orderPrice) {
                return $query->where('min_price', null)->orWhere('min_price', '<=', $orderPrice);
            });

            function checkUserOrderBetweenDate($user, $start, $finish){
                if($start !== null && $finish !== null){
                    $orders = Order::where([['user_id', '=', $user], ['date', '>=', $start], ['date', '<=', $finish]]);
                    if($orders->count() !== 0){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }

            if($allDiscounts->count() !== 0){
                $allDiscounts = $allDiscounts->get();
                $shippingReducedPrice = 0;
                $orderReducedPrice = 0;
                foreach($allDiscounts as $discount){
                    if(($discount->start_date === null && $discount->finish_date === null) ||
                    !checkUserOrderBetweenDate($userId, $discount->start_date, $discount->finish_date)){
                        $discountUserDependencies = $discount->users;
                        $discountProductDependencies = $discount->products;
                        $discountCategoryDependencies = $discount->categories;
                        $discountProvinceDependencies = $discount->provinces;
                        $dependencyPermission = true;
                        if($discountUserDependencies->count() !== 0){
                            $found = false;
                            foreach($discountUserDependencies as $userDependency){
                                if($userDependency->dependency_id === (integer)$userId){
                                    $found = true;
                                    break;
                                }
                            }
                            if($found == false){
                                $dependencyPermission = false;
                            }
                        }
                        if($dependencyPermission && $discountProvinceDependencies->count() !== 0){
                            $found = false;
                            foreach($discountProvinceDependencies as $provinceDependency){
                                if($provinceDependency->dependency_id === (integer)$provinceId){
                                    $found = true;
                                    break;
                                }
                            }
                            if($found == false){
                                $dependencyPermission = false;
                            }
                        }
                        if($dependencyPermission && $discountProductDependencies->count() !== 0){
                            $found = false;
                            foreach($discountProductDependencies as $productDependency){
                                if(in_array($productDependency->dependency_id, $productsInformationObject->productIds)){
                                    $found = true;
                                    break;
                                }
                            }   
                            if($found == false){
                                $dependencyPermission = false;
                            }    
                        }
                        if($dependencyPermission && $discountCategoryDependencies->count() !== 0){
                            $found = false;
                            foreach($discountCategoryDependencies as $categoryDependency){
                                if(in_array($categoryDependency->dependency_id, $productsInformationObject->categoryIds)){
                                    $found = true;
                                    break;
                                }
                            }
                            if($found == false){
                                $dependencyPermission = false;
                            }
                        }
                        if($dependencyPermission){
                            if($discount->type_id === 3){
                                if($discount->price !== null){
                                    $shippingReducedPrice += $discount->price;
                                }else if($discount->percent!== null){
                                    $shippingReducedPrice += ($discount->percent / 100) * $shippingPrice;
                                }
                                if($discount->max_price !== null){
                                    if($shippingReducedPrice > $discount->max_price){
                                        $shippingReducedPrice = $discount->max_price;
                                    }
                                }
                            }else if($discount->type_id === 4){
                                if($discount->price !== null){
                                    $orderReducedPrice += $discount->price;
                                }else if($discount->percent!== null){
                                    $orderReducedPrice += ($discount->percent / 100) * $orderPrice;
                                }
                                if($discount->max_price !== null){
                                    if($orderReducedPrice > $discount->max_price){
                                        $orderReducedPrice = $discount->max_price;
                                    }
                                }
                            }
                        }else{
                            echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'does not have dependency permission'));
                        }
                    }else{
                        echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'user had order between start and finish date'));
                    }
                }
                echo json_encode(array('status' => 'done', 'discount' => true, 'shippingPrice' => (integer)(($shippingPrice - $shippingReducedPrice)/100) * 100, 'shippingPercent' => (integer)(($shippingReducedPrice/$shippingPrice)*100), 'orderPrice' => (integer)(($orderPrice - $orderReducedPrice)/100) * 100, 'orderPercent' => (integer)(($orderReducedPrice/$orderPrice)*100)));
            }else{
                echo json_encode(array('status' => 'done', 'discount' => false, 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function getAllDiscounts(Request $request){
        
    }

    public function getDiscountRelevantDependencies(Request $request){
        if(isset($request->discountId) && isset($request->dependencyType)){
            $discountId = $request->discountId;
            $dependencyType = $request->dependencyType;
            $discount = Discount::where('id', $discountId);
            if($discount->count() !== 0){
                if($dependencyType === 'user'){
                    $userDependencies = DiscountDependency::where('discount_id', $discountId)->where('type_id', 4);
                    if($userDependencies->count() !== 0){
                        $userDependencies = $userDependencies->get();
                        $usersArray = array();
                        foreach($userDependencies as $userDependency){
                            $name = '';
                            $username = '';
                            $user = User::where('id', $userDependency->dependency_id);
                            if($user->count() !== 0){
                                $user = $user->first();
                                $name = $user->name;
                                $username = $user->username;
                            }else{
                                $name = 'یافت نشد';
                                $username = 'یافت نشد';
                            }
                            array_push($usersArray, array('id' => $userDependency->id, 'userId' => $userDependency->dependency_id, 'username' => $username, 'name' => $name));
                        }
                        echo json_encode(array('status' => 'done', 'message' => 'users found successfully', 'users' => $usersArray));
                    }else{
                        echo json_encode(array('status' => 'done', 'message' => 'users not found', 'users'=> array()));
                    }
                }else if($dependencyType === 'province'){
                    $provinceDependencies = DiscountDependency::where('discount_id', $discountId)->where('type_id', 3);
                    if($provinceDependencies->count() !== 0){
                        $provinceDependencies = $provinceDependencies->get();
                        $provincesArray = array();
                        foreach($provinceDependencies as $provinceDependency){
                            $province = Province::where('id', $provinceDependency->dependency_id)->first();
                            array_push($provincesArray, array('id' => $provinceDependency->id, 'provinceId' => $provinceDependency->dependency_id, 'name' => $province->name));
                        }
                        echo json_encode(array('status' => 'done', 'message' => 'provinces found successfully', 'provinces' => $provincesArray));
                    }else{
                        echo json_encode((array('status' => 'done', 'message' => 'there is not any province', 'provinces' => array())));
                    }
                }else if($dependencyType === 'product'){
                    $productDependencies = DiscountDependency::where('discount_id', $discountId)->where('type_id', 1);
                    if($productDependencies->count() !== 0){
                        $productDependencies = $productDependencies->get();
                        $productsArray = array();
                        foreach($productDependencies as $productDependency){
                            $product = Product::where('id', $productDependency->dependency_id)->first();
                            array_push($productsArray, array('id' => $productDependency->id, 'productId' => $productDependency->dependency_id, 'name' => $product->prodName_fa));
                        }
                        echo json_encode(array('status' => 'done', 'message' => 'products found successfully', 'products' => $productsArray));
                    }else{
                        echo json_encode((array('status' => 'done', 'message' => 'there is not any product', 'products' => array())));
                    }
                }else if($dependencyType === 'category'){
                    $categoryDependencies = DiscountDependency::where('discount_id', $discountId)->where('type_id', 2);
                    if($categoryDependencies->count() !== 0){
                        $categoryDependencies = $categoryDependencies->get();
                        $categoriesArray = array();
                        foreach($categoryDependencies as $categoryDependency){
                            $category = Category::where('id', $categoryDependency->dependency_id)->first();
                            array_push($categoriesArray, array('id' => $categoryDependency->id, 'categoryId' => $categoryDependency->dependency_id, 'name' => $category->name));
                        }
                        echo json_encode(array('status' => 'done', 'message' => 'categories found successfully', 'categories' => $categoriesArray));
                    }else{
                        echo json_encode((array('status' => 'done', 'message' => 'there is not any category', 'categories' => array())));
                    }
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enought parameter'));
        }
    }

    public function getAllProvinces(){
        $provinces = Province::all();
        $provincesArray = array();
        foreach($provinces as $province){
            array_push($provincesArray, array('id' => $province->id, 'name' => $province->name));
        }
        echo json_encode(array('status' => 'done', 'message' => 'provinces got successfully', 'provinces' => $provincesArray));
    }

    public function searchRelevantDependencies(Request $request){
        if(isset($request->dependencyType)){
            if($request->dependencyType === 'product'){
                if(isset($request->key)){
                    $key = $request->key;
                    $products = Product::select('id', 'prodName_fa')->where('prodStatus', 1)->where('prodName_fa', 'like', '%' . $key . '%');
                    if($products->count() !== 0){
                        $products = $products->take(10)->get();
                        $productsArray = array();
                        foreach($products as $product){
                            array_push($productsArray, array('id' => $product->id, 'name' => $product->prodName_fa));
                        }
                        echo json_encode(array('status' => 'done', 'message' => 'products got successfully', 'products' => $productsArray));
                    }else{
                        echo json_encode(array('status' => 'done', 'message' => 'there is not any product', 'products' => array()));
                    }
                }else{
                    $products = Product::select('id', 'prodName_fa')->where('prodStatus', 1);
                    if($products->count() !== 0){
                        $products = $products->take(10)->get();
                        $productsArray = array();
                        foreach($products as $product){
                            array_push($productsArray, array('id' => $product->id, 'name' => $product->prodName_fa));
                        }
                        echo json_encode(array('status' => 'done', 'message' => 'products got successfully', 'products' => $productsArray));
                    }else{
                        echo json_encode(array('status' => 'done', 'message' => 'there is not any product', 'products' => array()));
                    }
                }
            }else if($request->dependencyType === 'category'){
                if(isset($request->key)){
                    $key = $request->key;
                    $categories = Category::select('id', 'name')->where('name', 'like', '%' . $key . '%');
                    if($categories->count() !== 0){
                        $categories = $categories->take(10)->get();
                        $categoriesArray = array();
                        foreach($categories as $category){
                            array_push($categoriesArray, array('id' => $category->id, 'name' => $category->name));
                        }
                        echo json_encode(array('status' => 'done', 'message' => 'categories got successfully', 'categories' => $categoriesArray));
                    }else{
                        echo json_encode(array('status' => 'done', 'message' => 'there is not any category', 'categories' => array()));
                    }
                }else{
                    $categories = Category::select('id', 'name');
                    if($categories->count() !== 0){
                        $categories = $categories->take(10)->get();
                        $categoriesArray = array();
                        foreach($categories as $category){
                            array_push($categoriesArray, array('id' => $category->id, 'name' => $category->name));
                        }
                        echo json_encode(array('status' => 'done', 'message' => 'categories got successfully', 'categories' => $categoriesArray));
                    }else{
                        echo json_encode(array('status' => 'done', 'message' => 'there is not any category', 'categories' => array()));
                    }
                }
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function getDiscountsBasicInformation(Request $request){
        $discounts = Discount::select('id', 'title', 'type_id', 'status')->orderBy('id', 'DESC');
        if($discounts->count() !== 0){
            $discounts = $discounts->get();
            $discountsArray = array();
            foreach($discounts as $discount){
                $type = '';
                if($discount->type_id === 1){
                    $type = 'product';
                }else if($discount->type_id === 2){
                    $type = 'category';
                }else if($discount->type_id === 3){
                    $type = 'province';
                }else if($discount->type_id === 4){
                    $type = 'order';
                }else if($discount->type_id === 5){
                    $type = 'multi-product';
                }
                array_push($discountsArray, array('discountId' => $discount->id, 'title' => $discount->title, 'type' => $type, 'status' => $discount->status));
            }
            echo json_encode(array('status' => 'done', 'message' => 'found the discounts', 'discounts' => $discountsArray));
        }else{
            echo json_encode(array('status' => 'done', 'message' => 'there is not any discount', 'discounts' => array()));
        }
    }

    public function toggleDiscount(Request $request){
        if(isset($request->discountId)){
            $discountId = $request->discountId;
            $discount = Discount::where('id', $discountId);
            if($discount->count() !== 0){
                $discount = $discount->first();
                if($discount->status == 0){
                    $discount->status = 1;
                }else{
                    $discount->status = 0;
                }
                if($discount->save()){
                    echo json_encode(array('status' => 'done', 'message' => 'discount updated successfully', 'newStatus' => $discount->status));
                }else{
                    echo json_encode(array('status' => 'failed', 'message' => 'an error occured while saving the discount'));
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'discount not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function filteredPaginateddiscounts(Request $request){
        if(!isset($request->page) || !isset($request->type) || !isset($request->active) || !isset($request->startDate) || !isset($request->finishDate)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $page = $request->page;
        $type = $request->type;
        $active = $request->active;
        $startDate = $request->startDate;
        $finishDate = $request->finishDate;
        $time = time();

        $activeFilterQuery = "";
        $typeFilterQuery = "";
        $startDateQuery = "";
        $finishDateQuery = "";

        if($active === 1){
            $activeFilterQuery = " AND D.status = 1 AND ( D.expiration_date IS NULL OR D.expiration_date <= $time ) AND ((D.start_date IS NULL AND D.finish_date IS NULL) OR (D.start_date <= $time AND D.finish_date >= $time)) ";  
        }

        if($type === 'product'){
            $typeFilterQuery = " WHERE D.type_id = 1 ";
        }else if($type === 'category'){
            $typeFilterQuery = " WHERE D.type_id = 2 ";
        }else if($type === 'order'){
            $typeFilterQuery = " WHERE D.type_id = 4 ";
        }else if($type === 'shipping'){
            $typeFilterQuery = " WHERE D.type_id = 3 ";
        }else if($type === 'multi-product'){
            $typeFilterQuery = " WHERE D.type_id = 5 ";
        }else if($type === 'code'){
            $typeFilterQuery = " WHERE (D.type_id = 4 OR D.type_id = 3) AND D.code IS NOT NULL " ; 
        }else if($type == 'all'){
            $typeFilterQuery = " WHERE D.id > 0 ";
        }

        if($startDate > 0){
            $startDateQuery = " AND D.date >= $startDate ";
        }
        if($finishDate > 0){
            $finishDateQuery = " AND D.date <= $finishDate";
        }

        $allDiscounts = DB::select(
            "SELECT D.id AS discountId, D.title, D.type_id AS typeId, DT.fa_name AS `type`, D.expiration_date, D.start_date, D.finish_date, D.status 
            FROM discounts D 
            INNER JOIN discount_types DT ON DT.id = D.type_id 
            $typeFilterQuery $activeFilterQuery $startDateQuery $finishDateQuery 
            ORDER BY D.date DESC, D.id DESC "
        );

        if(count($allDiscounts) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'count' => 0, 'maxPage' => 0, 'discounts' => [], 'message' => 'could not find any discount', 'umessage' => 'تخفیفی یافت نشد'));
            exit();
        }

        $count = count($allDiscounts);
        $i = ($page - 1) * 20;
        $selectedDiscounts = [];
        for($i; $i<$count && count($selectedDiscounts)<20; $i++){
            $discount  = $allDiscounts[$i];
            $discount->smartStatus = 0;
            if($discount->status === 1 && 
                ($discount->expiration_date === NULL || $discount->expiration_date >= $time) &&
                ($discount->start_date === NULL || $discount->start_date <= $time) && 
                ($discount->finish_date === NULL || $discount->finish_date >= $time )){
                
                $discount->smartStatus = 1;
            }
            array_push($selectedDiscounts, $discount);
        }

        echo json_encode(array('status' => 'done', 'found' => true, 'count' => $count, 'maxPage' => ceil($count / 20), 'discounts' => $selectedDiscounts, 'message' => 'discounts found successfully'));
    }

    public function filteredPaginatedDiscountReports(Request $request){
        if(!isset($request->page) || !isset($request->type) || !isset($request->username) || !isset($request->orderId) || !isset($request->discountId) || !isset($request->startDate) || !isset($request->finishDate)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $page = $request->page;
        $orderId = $request->orderId;
        $username = $request->username;
        $startDate = $request->startDate;
        $finishDate = $request->finishDate;
        $type = $request->type;
        $discountId = $request->discountId;
        
        $orderIdQuery = '';
        $discountIdQuery = '';
        $usernameQuery = '';
        $dateQuery = '';
        $typeQuery = '';

        if($type === 'all'){
            $typeQuery = " WHERE DL.id > 0 ";
        }else if($type === "confirmed"){
            $typeQuery = " WHERE O.stat NOT IN (6,7) ";
        }else if($type === "delivered"){
            $typeQuery = " WHERE O.stat = 9 ";
        }else if($typeQuery === 'canceled'){
            $typeQuery = " WHERE O.stat = 7 ";
        }else if($type === 'notPaid'){
            $typeQuery = " WHERE O.stat = 6 ";
        }else if($type === 'code'){
            $typeQuery = " WHERE D.code IS NOT NULL AND D.type IN ('order', 'shipping')";
        }else if($type === 'product'){
            $typeQuery = " WHERE D.type = 'product' ";
        }else if($type === 'category'){
            $typeQuery = " WHERE D.type = 'category' ";
        }else if($type === 'order'){
            $typeQuery = " WHERE D.type = 'order' ";
        }else if($type === 'shipping'){
            $typeQuery = " WHERE D.type = 'shipping' ";
        }
        
        if($orderId !== 0){
            $orderIdQuery = " AND DL.order_id = $orderId ";
        }
        if($discountId !== 0){
            $discountIdQuery = " AND D.id = $discountId ";
        }
        if($username !== '0'){
            $usernameQuery = " AND U.username = '$username' ";
        }
        if($startDate !== 0 && $finishDate !== 0){
            $dateQuery = " AND O.date >= $startDate AND O.date <= $finishDate ";
        }
        $reports = DB::select(
            "SELECT DL.id AS logId, DL.discount_id AS discountId, D.title AS discountTitle, U.username, U.name AS uname, DL.order_id AS orderId, O.date 
            FROM discount_logs DL INNER JOIN users U ON DL.user_id = U.id INNER JOIN orders O ON DL.order_id = O.id INNER JOIN discounts D ON DL.discount_id = D.id
            $typeQuery $orderIdQuery $discountIdQuery $usernameQuery $dateQuery ORDER BY DL.id DESC "
        );
        $count = count($reports);
        if($count === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'sounrce' => 'c', 'count' => 0, 'reports' => [], 'message' => 'could not find any report', 'umessage' => 'گزارشی یافت نشد'));
            exit();
        }
        $selectedReports = [];
        $i = ($page - 1) * 20;
        for($i ; $i<$count && count($selectedReports)<20 ; $i++){
            array_push($selectedReports, $reports[$i]);    
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'count' => $count, 'reports' => $selectedReports, 'message' => 'reports found successfully'));
    }

    public function getSubCategories($categoryId){
        $response = $categoryId . '';
        $scs = DB::select("SELECT * FROM category WHERE parentID = $categoryId ");
        foreach($scs as $category){
            $response = $response . ', ' . $this->getSubCategories($category->id);
        }
        return $response;
    }

    public function noPaginatedFilterCategoryProducts(Request $request){
        if(!isset($request->categoryId) || !isset($request->discountId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough information', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $categoryId = intval($request->categoryId);
        $discountId = $request->discountId;

        $priceQuery = '';
        $stockQuery = '';
        $sleepQuery = '';
        $factorQuery = '';

	$category = DB::select("SELECT id, url FROM category WHERE id = $categoryId LIMIT 1");
	$category = $category[0];

	$categoryUrl = $category->url . '%';

        if(isset($request->minPrice)){
            $priceQuery = $priceQuery . " AND PP.price >= $request->minPrice ";
        }
        if(isset($request->maxPrice)){
            $priceQuery = $priceQuery . " AND PP.price <= $request->maxPrice " ;
        }

        if(isset($request->minStock)){
            $stockQuery = $stockQuery . " AND PP.stock >= $request->minStock ";
        }
        if(isset($request->maxStock)){
            $stockQuery = $stockQuery . " AND PP.stock <= $request->maxStock " ;
        }

        if(isset($request->minSleep)){
            $sleepQuery = $sleepQuery . " AND (PS.sleep_daily * P.stock) >= $request->minSleep ";
        }
        if(isset($request->maxSleep)){
            $sleepQuery = $sleepQuery . " AND (PS.sleep_daily * P.stock) <= $request->maxSleep ";
        }
        if(isset($request->minFactor)){
            $factorQuery = $factorQuery . " AND PS.last_factor >= $request->minFactor ";
        }
        if(isset($request->maxFactor)){
            $factorQuery = $factorQuery . " AND PS.last_factor <= $request->maxFactor ";
        }

	$subCategories = $this->getSubCategories($categoryId);
        
        $products = DB::select( 
            "SELECT P.id, 
                P.prodName_fa AS productName, 
                P.url AS productUrl, 
                P.prodUnite AS productUnit, 
                P.buyPrice AS productBuyPrice, 
                PP.price AS productPrice, 
                PP.stock AS productStock, 
		PP.id AS productPackId, 
                (PS.sleep_daily * P.stock) AS productSleep, 
                PS.profit_margin AS productProfitMargin 
            FROM products P 
                INNER JOIN product_category PC ON P.id = PC.product_id 
		INNER JOIN category C ON PC.category = C.id 
		INNER JOIN products_location PL ON P.id = PL.product_id 
                INNER JOIN product_pack PP ON P.id = PP.product_id 
                INNER JOIN product_info `PI` ON P.id = `PI`.product_id  
                INNER JOIN product_statistics PS ON P.id = PS.product_id 
            WHERE C.id IN ($subCategories) AND 
		PC.kind = 'primary' AND 
                P.prodStatus = 1 AND 
                PL.stock > 0 AND 
		PL.anbar_id = 1 AND 
                PP.status = 1 AND 
                PL.pack_stock > 0 
                AND P.id NOT IN ( 
                    SELECT dependency_id 
                    FROM discount_dependencies 
                    WHERE discount_id = $discountId 
                ) 
                $priceQuery 
                $stockQuery 
                $sleepQuery 
                $factorQuery
            ORDER BY P.prodName_fa ASC "
        );

        if(count($products) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'source' => 'c', 'message' => 'there is any available product in this filter', 'umessage' => 'موردی یافت نشد', 'products' => []));
            exit();
        }

	$selectedProducts = [];
	$selectedProductIds = [];
	foreach($products as $p){
		if(array_search($p->id, $selectedProductIds) === false){
			array_push($selectedProductIds, $p->id);
			array_push($selectedProducts, $p);
		}
	}
        
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'products successfully found', 'products' => $selectedProducts));
    }

    public function addProductsToMultiProductDiscount(Request $request){
        if(!isset($request->discountId) || !isset($request->products)){
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $discountId = $request->discountId;
        $discountInformation = DB::select(
            "SELECT id FROM discounts WHERE id = $discountId AND `type_id` = 5 "
        );
        if(count($discountInformation) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'discount does not exist or it is not multi-product', 'umessage' => 'این تخفیف وجود ندارد و یا از نوع محصول ویژه نیست'));
            exit();
        }
        $products = $request->products;
        foreach($products as $p){
            $price = 0;
            $percent = 0;
            $finalStock = $p['finalStock'];
            $productId = $p['id'];
            if($p['percent'] !== 0){
                $percent = $p['percent'];
            }else if($p['price']){
                $price = $p['price'];
            }
            DB::insert(
                "INSERT INTO discount_dependencies (
                    discount_id, `type_id`, dependency_id, percent, price, final_stock 
                ) VALUES (
                    $discountId, 1, $productId, $percent, $price, $finalStock
                )"
            );
        }
        
        echo json_encode(array('status' => 'done', 'message' => 'discount dependencies successfully inserted'));
        exit();
    }   

    public function editMultiProductDiscountGeneralInformation(Request $request){
        if(!isset($request->discountId) || 
            !isset($request->title) || 
            !isset($request->description) ||
            !isset($request->startDate) || 
            !isset($request->finishDate) ||
            !isset($request->expirationDate) ||
            !isset($request->status)){

            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough information', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        $discountId = $request->discountId;
        $title = $request->title;
        $description = $request->description;
        $startDate = $request->startDate;
        $finishDate = $request->finishDate;
        $expirationDate = $request->expirationDate;
        $status = $request->status;

        if($startDate == 0){
            $startDate = "NULL";
        }
        if($finishDate == 0){
            $finishDate = "NULL";
        }
        if($expirationDate == 0){
            $expirationDate = "NULL";
        }

        $queryResult = DB::update(
            "UPDATE discounts 
            SET title = '$title', 
                `description` = '$description', 
                `start_date` = $startDate, 
                finish_date = $finishDate, 
                expiration_date = $expirationDate, 
                `status` = $status  
            WHERE id = $discountId AND 
                `type_id` = 5 "
        );
        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'error while updating the discount', 'umessage' => 'خطا هنگام بروزرسانی تخفیف'));
            exit();
        }
        echo json_encode(array('status' => 'done', 'message' => 'discount successfully updated'));
    }

    public function multiProductDiscountProducts(Request $request){
        if(!isset($request->discountId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $discountId = $request->discountId;
        $discount = DB::select(
            "SELECT * 
            FROM discounts 
            WHERE id = $discountId 
            LIMIT 1 "
        );
        if(count($discount) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'discount not found', 'umessage' => 'تخفیف موردنظر یافت نشد'));
            exit();
        }
        $discount = $discount[0];
        if($discount->type_id !== 5){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'discount is not multi product', 'umessage' => 'تخفیف از نوع ویژه نیست'));
            exit();
        }
        $products = DB::select(
            "SELECT P.id AS productId,  
                P.prodName_fa AS productName, 
                P.url AS productUrl, 
                PP.stock AS productStock, 
                PP.price AS productPrice, 
                (PS.sleep_daily * P.stock) AS productSleep, 
                PS.profit_margin AS productProfitMargin, 
                DD.percent AS discountPercent, 
                DD.price AS discountPrice, 
                DD.final_stock AS discountFinalStock 
            FROM discount_dependencies DD 
            INNER JOIN products P ON DD.dependency_id = P.id 
            INNER JOIN product_pack PP ON DD.dependency_id = PP.product_id 
            INNER JOIN product_statistics PS ON DD.dependency_id = PS.product_id 
            WHERE DD.discount_id = $discountId AND 
                PP.status = 1 AND 
                DD.type_id = 1 "
        );
        if(count($products) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'nothing found', 'umessage' => 'موردی یافت نشد'));
        }else{
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'products successfully found', 'products' => $products));

        }
    }

    public function editMultiProductDiscountProductInformation(Request $request){
        if(!isset($request->discountId) || !isset($request->productId) || !isset($request->price) || !isset($request->percent) || !isset($request->finalStock)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough information', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $discountId = $request->discountId;
        $productId = $request->productId;
        $percent = $request->percent;
        $price = $request->price;
        $finalStock = $request->finalStock;

        $discount = DB::select(
            "SELECT id, `type_id` FROM discounts WHERE id = $discountId"
        );
        if(count($discount) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'discount not found', 'umessage' => 'تخفیف موردنظر یافت نشد'));
            exit();
        }
        $discount = $discount[0];
        if($discount->type_id !== 5){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'discount is not multi product', 'umessage' => 'نوع تخفیف مورد نظر ویژه محصولات نیست'));
            exit();
        }

        $updateResult = DB::update(
            "UPDATE discount_dependencies 
            SET price = $price, 
                percent = $percent, 
                final_stock = $finalStock 
            WHERE 
                discount_id = $discountId AND 
                dependency_id = $productId AND 
                `type_id` = 1"
        );
        
        if(!$updateResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'error while updating the discount dependency information', 'umessage' => 'خطا در بروزرسانی اطلاعات محصول'));
        }else{
            echo json_encode(array('status' => 'done', 'source' => 'c', 'message' => 'successfully updated'));
        }
    }

    public function removeProductFromMultiProductDiscount(Request $request){
        if(!isset($request->discountId) || !isset($request->productId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $discountId = $request->discountId;
        $productId = $request->productId;
        $discount = DB::select(
            "SELECT id, `type_id` FROM discounts WHERE id = $discountId LIMIT 1"
        );
        if(count($discount) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'discount not found', 'umessage' => 'تخفیف موردنظر یافت نشد'));
            exit();
        }
        $discount = $discount[0];
        if($discount->type_id !== 5){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'discount is not multi product', 'umessage' => 'تخفیف مورد نظر ویژه محصولات نیست'));
            exit();
        }
        try{
            DB::delete(
                "DELETE FROM discount_dependencies 
                WHERE discount_id = $discountId AND 
                    dependency_id = $productId AND 
                    `type_id` = 1"
            );
            echo json_encode(array('status' => 'done', 'message' => 'dependency successfully removed'));
        }catch(Exception $e){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => $e->getMessage(), 'umessage' => 'خطا هنگام حذف محصول'));
        }
    }

    public function multiProductDiscountInformation(Request $request){
        if(!isset($request->discountId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $discountId = $request->discountId;

        $discount = DB::select(
            "SELECT title, `description`,  `start_date`, `finish_date`, expiration_date, `status`  
            FROM discounts 
            WHERE id = $discountId AND `type_id` = 5  
            LIMIT 1 "
        );

        if(count($discount) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'discount not found', 'umessage' => 'تخفیف موردنظر یافت نشد'));
            exit();
        }

        $discount = $discount[0];

        echo json_encode(array('status' => 'done', 'message' => 'discount information successfully found', 'information' => $discount));
    }

    
}
