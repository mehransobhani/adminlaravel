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

class QueryController extends Controller
{
    public function exportExcel($headers, $content){
        $i = 0;
        $export = '<head><meta charset="UTF-8"></head><table><tr><th>SR.N.</th>';
        foreach($headers as $h){
            $export = $export . "<th>$h</th>";
        }
        $export = $export . '</tr>';
        foreach($content as $c){
            $export  = $export . "<tr><td>$i</td>";
            foreach($c as $key => $value){
                $export = $export . "<td>$value</td>";
            }
            $export = $export . '</tr>';
            $i++;
        }
        $export = $export . "</table>";
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename= download.xls");
        echo $export;
    }
    public function getProductStockBuyLogs(Request $request){
        $logs = DB::select(
            "SELECT DISTINCT PS.product_id, P.prodName_fa, P.prodUnite, SUM(PS.changed_count) AS sum
            FROM product_stock PS INNER JOIN products P ON P.id = PS.product_id 
            WHERE PS.date >= 1584649800 and PS.date <= 1616185800 and PS.kind = 2 and PS.changed_count > 0 
            GROUP BY PS.product_id  
            ORDER BY PS.product_id  DESC"
        );
        if(count($logs) === 0){
            echo "there is not any available result";
            exit();
        }
        /*$export =
        '<head><meta charset="UTF-8"></head><table>
            <tr>
                <th>SR.N.</th>
                <th>PRODUCT_ID</th>
                <th>PRODUCT_NAME</th>
                <th>PRODUCT_UNIT</th>
                <th>PRODUCT_BUY_COUNT</th>
            </tr>';
        $i = 0;
        foreach($logs as $l){
            $export = $export . "<tr><td>$i</td><td>" . $l->product_id . "</td><td>$l->prodName_fa</td><td>$l->prodUnite</td><td>$l->sum</td></tr>";
            $i++;
        }
        $export = $export . "</table>";
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename= download.xls");
        echo $export;
        */
        $this->exportExcel(['PRODUCT_ID', 'PRODUCT_NAME', 'PRODUCT_UNIT', 'PRODUCT_BUY_COUNT'], $logs);
        
    }
    public function getProductStockSellLogs(Request $request){
        /*$logs = DB::select(
            "SELECT DISTINCT PS.product_id, P.prodName_fa, P.prodUnite, SUM(PS.changed_count) AS sum
            FROM product_stock PS INNER JOIN products P ON P.id = PS.product_id 
            WHERE PS.date >= 1584649800 and PS.date <= 1616185800 and PS.kind IN (4,5,6,8,13,15)  
            GROUP BY PS.product_id  
            ORDER BY PS.product_id  DESC "
        );
        if(count($logs) === 0){
            echo "there is not any available result";
            exit();
        }
        $this->exportExcel(['PRODUCT_ID', 'PRODUCT_NAME', 'PRODUCT_UNIT', 'PRODUCT_SELL_COUNT'], $logs);*/
        /*$logs = DB::select(
            "SELECT DISTINCT NPI.product_id, PS.name
            FROM new_prefactor NP INNER JOIN new_prefactor_items NPI ON NP.id = NPI.factor_id INNER JOIN product_source PS ON NP.product_source_id = PS.id
            WHERE NP.date <= 1616185800 AND NP.date >= 1584649800 
            order by NP.date DESC, NPI.product_id desc"
        );
        $this->exportExcel(['product_id', 'name'], $logs);*/
            $logs = DB::select(
                "SELECT DISTINCT NPI.product_id, PS.name
                FROM new_prefactor NP INNER JOIN new_prefactor_items NPI ON NP.id = NPI.factor_id INNER JOIN product_source PS ON NP.product_source_id = PS.id
                WHERE product_id = 2184
                order by NPI.product_id desc"
            );
        var_dump($logs);
    }

    public function noPaginatedFilterCategoryProducts(Request $request){
        if(!isset($request->categoryId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough information', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $categoryId = $request->categoryId;
        
        $priceQuery = '';
        $stockQuery = '';
        $sleepQuery = '';
        $factorQuery = '';

        if(isset($request->minPrice) && isset($request->maxPrice)){
            $priceQuery = " AND PP.price >= $request->minPrice AND PP.price <= $request->maxPrice " ;
        }

        if(isset($request->minStock) && isset($request->maxStock)){
            $stockQuery = " AND PP.stock >= $request->minStock AND PP.stock <= $request->maxStock " ;
        }

        if(isset($request->minSleep) && isset($request->maxSleep)){
            $sleepQuery = " AND (PS.sleep_daily * P.stock) >= $request->minSleep AND (PS.sleep_daily * P.stock) <= $request->maxSleep ";
        }

        if(isset($request->minFactor) && isset($request->maxFactor)){
            $factorQuery = " AND PS.last_factor >= $request->minFactor AND PS.last_factor <= $request->maxFactor ";
        }
        
        $products = DB::select(
            "SELECT P.id, 
                P.prodName_fa AS productName, 
                P.url AS productUrl, 
                (PP.stock * PP.count) AS productPrice, 
                P.prodUnite AS productUnit, 
                P.price AS productPrice, 
                PP.stock AS productStock, 
            FROM products P 
                INNER JOIN product_category PC ON P.id = PC.product_id 
                INNER JOIN product_pack PP ON P.id = PP.product_id 
                INNER JOIN product_info `PI` ON P.id = `PI`.product_id  
                INNER JOIN product_statistics PS ON P.id = PS.product_id 
            WHERE C.id = $categoryId AND 
                P.prodStatus = 1 AND 
                P.stock > 0 AND 
                PP.status = 1 AND 
                PP.stock > 0 AND 
                (PP.stock * PP.count <= P.stock) 
                $priceQuery 
                $stockQuery 
                $sleepQuery 
                $factorQuery
            ORDER BY P.name ASC "
        );

        if(count($products) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'source' => 'c', 'message' => 'there is any available product in this filter', 'umessage' => 'موردی یافت نشد', 'products' => []));
            exit();
        }
        
        json_encode(array('status' => 'done', 'found' => true, 'message' => 'products successfully found', 'products' => $products));
    }

    public function getUsersWhoBoughtCourse(Request $request){
        $usersInformation = DB::select(
            "SELECT U.ex_user_id, U.name, U.username FROM users U WHERE U.id IN (
                SELECT DISTINCT CU.user_id FROM course_user CU WHERE CU.type IN ('class', 'bundle') AND CU.create_at >= 1584649811  
            ) ORDER BY U.ex_user_id ASC"
        );
        $this->exportExcel(['USER_ID', 'NAME', 'PHONE_NUMBER'], $usersInformation);
    }

    public function getUsersWhoDontOrder(Request $request){
        $usersInformation = DB::select(
            "SELECT U.ex_user_id, U.name, U.username FROM users U WHERE U.id NOT IN (
                SELECT DISTINCT O.user_id FROM orders O WHERE O.stat = 9 AND O.date >= 1584649811  
            ) ORDER BY U.ex_user_id ASC"
        );
        $this->exportExcel(['USER_ID', 'NAME', 'PHONE_NUMBER'], $usersInformation);
    }

    public function getUsersWhoComeButDidntBuy(Request $request){
        /*$usersInformation = DB::select(
            "SELECT U.ex_user_id, U.name, U.username FROM users U WHERE U.id NOT IN (
                SELECT DISTINCT O.user_id FROM orders O WHERE O.stat = 9 AND O.date >= 1584649811  
            ) ORDER BY U.ex_user_id ASC"
        );*/
        $usersInformation = DB::select(
            "SELECT U.ex_user_id, U.name, U.username FROM users U WHERE U.id IN (SELECT DISTINCT OAT.user_id FROM oauth_access_tokens OAT) 
            AND U.id NOT IN (SELECT DISTINCT CU.user_id FROM course_user CU WHERE CU.create_at >= 1584649811 AND CU.type IN ('class', 'bundle')) 
            ORDER BY U.ex_user_id ASC "
        );
        $this->exportExcel(['USER_ID', 'NAME', 'PHONE_NUMBER'], $usersInformation);
    }

    /*public function customQuery(Request $request){
        $productIds = DB::select(
            "SELECT DISTINCT
                PS.product_id, P.prodName_fa, P.prodUnite
            FROM product_stock PS 
            INNER JOIN products P ON P.id = PS.product_id 
            WHERE 
                PS.date >= 1553113860 AND 
                PS.date <= 1584563460 AND 
                PS.kind = 2
            "
        );
        $response = [];
        foreach($productIds as $productId){
            $logs = DB::select(
                "SELECT
                    changed_count, 
                    factor_id, 
                    new_factor_id  
                FROM product_stock
                WHERE 
                    product_id = $productId->product_id AND 
                    date >= 1553113860 AND 
                    date <= 1584563460 AND 
                    kind = 2 
                "
            );
            $count = 0;
            $countPrice = 0;
            foreach($logs as $log){
                if($log->factor_id !== null){
                    $factorInformation = DB::select(
                        "SELECT products FROM product_factor WHERE id = $log->factor_id LIMIT 1 "
                    );
                    if(count($factorInformation) !== 0){
                        $factorInformation = $factorInformation[0];
                        $products = $factorInformation->products;
                        if($products != ''){
                            $products = json_decode('{' . $products . '}');
                            foreach($products as $key => $value){
                                if($key == $productId->product_id){
                                    $count += $log->changed_count;
                                    $countPrice += $log->changed_count * $value->price;
                                }
                            }
                        } 
                    }
                }
            }
            $object = new stdClass();
            $object->productId = $productId->product_id;
            $object->productName = $productId->prodName_fa;
            $object->productUnit = $productId->prodUnite;
            $object->count = $count;
            $object->totalPrice = $countPrice;
            if($count !== 0){
                $object->averagePrice = $countPrice/$count;
            }else{
                $object->averagePrice = 0;
            }
            array_push($response, $object);
        }
        $this->exportExcel(['PRODUCT_ID', 'PRODUCT_NAME', 'PRODUCT_UNIT', 'BUY_COUNT', 'TOTAL_BUY_PRICE', 'AVERAGE_BUY_PRICE', ], $response);
    }*/

    /*
    public function customQuery(Request $request){
        $productIds = DB::select(
            "SELECT DISTINCT
                PS.product_id, P.prodName_fa, P.prodUnite
            FROM product_stock PS 
            INNER JOIN products P ON P.id = PS.product_id 
            INNER JOIN orders O ON PS.order_id = O.id  
            WHERE 
                PS.date >= 1553113860 AND 
                PS.date <= 1584563460 AND 
                PS.kind IN (5, 6) AND 
                O.stat = 9 
            "
        );
        $response = [];
        foreach($productIds as $productId){
            $count = 0;
            $countPrice = 0;
            $logs = DB::select(
                "SELECT OI.count, OI.pack_count, price 
                FROM order_items OI 
                INNER JOIN orders O ON OI.order_id = O.id 
                WHERE 
                    OI.product_id = $productId->product_id AND 
                    O.stat = 9 AND 
                    O.date >= 1553113860 AND 
                    O.date <= 1584563460
                "
            );
            foreach($logs as $log){
                $count += ($log->count * $log->pack_count);
                $countPrice += ($log->count * $log->price);
            }
            $object = new stdClass();
            $object->productId = $productId->product_id;
            $object->productName = $productId->prodName_fa;
            $object->productUnit = $productId->prodUnite;
            $object->count = $count;
            $object->totalPrice = $countPrice;
            if($count !== 0){
                $object->averagePrice = $countPrice/$count;
            }else{
                $object->averagePrice = 0;
            }
            array_push($response, $object);
        }
        $this->exportExcel(['PRODUCT_ID', 'PRODUCT_NAME', 'PRODUCT_UNIT', 'SELL_COUNT', 'TOTAL_SELL_PRICE', 'AVERAGE_SELL_PRICE', ], $response);
    }
*/
/*
    public function customQuery(Request $request){
        $productIds = DB::select(
            "SELECT DISTINCT 
                PS.product_id, P.prodName_fa, P.prodUnite
            FROM product_stock PS 
            INNER JOIN products P ON P.id = PS.product_id 
            WHERE 
                PS.date < 1553113860 AND 
                PS.kind = 2 
            "
        );
        $response = [];
        foreach($productIds as $productId){
            $log = DB::select(
                "SELECT factor_id, new_factor_id, changed_count  
                FROM product_stock 
                WHERE product_id = $productId->product_id AND kind = 2 AND date < 1553113860  AND ( factor_id IS NOT NULL OR new_factor_id IS NOT NULL ) 
                ORDER BY `date` DESC 
                LIMIT 1"
            );
            if(count($log) !== 0){
                $price = 0;
                $log = $log[0];
                $calculated = false;
                if($log->factor_id !== NULL){
                    $factorInformation = DB::select(
                        "SELECT products FROM product_factor WHERE id = $log->factor_id LIMIT 1 "
                    );
                    if(count($factorInformation) !== 0){
                        $factorInformation = $factorInformation[0];
                        $products = $factorInformation->products;
                        if($products != ''){
                            $products = json_decode('{' . $products . '}');
                            foreach($products as $key => $value){
                                if($key == $productId->product_id){
                                    $price = $value->price;
                                    $calculated = true;
                                }
                            }
                        } 
                    }
                }
                if(!$calculated && $log->new_factor_id !== NULL){
                    $factorInformation = DB::select(
                        "SELECT unit_price  
                        FROM new_prefactor_items 
                        WHERE factor_id = $log->new_factor_id AND 
                            product_id = $productId->product_id 
                        LIMIT 1"
                    );
                    if(count($factorInformation) !== 0){
                        $factorInformation = $factorInformation[0];
                        $price = $factorInformation->unit_price;
                        $calculated = true;
                    }
                }
                if($calculated){
                    $object = new stdClass();
                    $object->productId = $productId->product_id;
                    $object->productName = $productId->prodName_fa;
                    $object->productUnit = $productId->prodUnite;
                    $object->price = $price;
                    array_push($response, $object);
                }
            }
        }
        $this->exportExcel(['PRODUCT_ID', 'PRODUCT_NAME', 'PRODUCT_UNIT', 'LAST_BUY_PRICE'], $response);
    }*/

    public function customQuery(Request $request){
        $factorIds = DB::select(
            "SELECT DISTINCT new_factor_id 
            FROM product_stock 
            WHERE new_factor_id IS NOT NULL AND kind = 2
            order by new_factor_id ASC"
        );
        $response = [];
        foreach($factorIds as $factorId){
            $factor = DB::select(
                "SELECT id FROM new_prefactor WHERE id = $factorId->new_factor_id LIMIT 1"
            );
            if(count($factor) == 0){
                $productIds = DB::select(
                    "SELECT id FROM product_stock WHERE kind = 2 AND new_factor_id = $factorId->new_factor_id "
                );
                $object = new stdClass();
                $object->factorId = $factorId->new_factor_id;
                $object->factorCount = count($productIds);
                array_push($response, $object);
            }
        }
        $this->exportExcel(['FACTOR_ID', 'FACTOR_PRODUCTS_COUNT'], $response);
    }

    public function sth(Request $request){

    }
}