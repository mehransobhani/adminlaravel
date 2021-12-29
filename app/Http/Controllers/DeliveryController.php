<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceCity;
use App\Models\ServicePlan;
use App\Models\SP;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        echo 'index method called';
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        //
    }

    public function getCities(Request $request){
        $params = $request->all();
        if(isset($params['provinceId'])){
            $provinceId = $params['provinceId'];
            $province = Province::where('id', $provinceId)->first();
            if($province){
                $cities = $province->cities->where('status', 1);
                if($cities != null){
                    $response = array();
                    foreach($cities as $city){
                        array_push($response, array('cityId' => $city->id, 'cityName' => $city->city));
                    }
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                }else{
                    $response = array(
                        'error' => 'city not found',
                    );
                    echo json_encode($response);
                }
            }else{
                $response = array(
                    'error' => 'province not found',
                );
                echo json_encode($response);
            }
        }else{
            $response = array(
                'error' => 'not enough parameter',
            );
            echo json_encode($response);
        }
    }

    public function getServiceAndPrice (Request $request){
        $params = $request->all();

        if(isset($params['cityId']) && isset($params['weight'])){
            $cityId = $params['cityId'];
            $weight = $params['weight'];
            $city = City::where('id', $cityId)->first();
            if($city != null){
                $provinceId = $city->province->id;
                $services = ServiceCity::where('city_id', $cityId);
                $serviceIds = [];
                if($cityId != 1){
                    array_push($serviceIds, 3);
                }
                $servicesArray = [];
                $services = $services->get();
                foreach($services as $service){
                    if(!in_array($service->service_id, $serviceIds)){
                        array_push($serviceIds, $service->service_id);
                    }
                }
                
                /*###### finding services that work with city ######*/
                foreach($serviceIds as $serviceId){
                    $servicePlans = ServicePlan::where('service_id', $serviceId)->where('city_id', $cityId);
                    if($servicePlans !== null){
                        $servicePlans = $servicePlans->get();
                        foreach($servicePlans as $servicePlan){
                            if($servicePlan->min_weight !== null && $servicePlan->max_weight !== null){
                                if($servicePlan->min_weight <= $weight && $weight < $servicePlan->max_weight){
                                    array_push($servicesArray, array('serviceId' => $servicePlan->service_id, 'serviceName' => Service::where('id', $servicePlan->service_id)->first()->name, 'price' => $servicePlan->price));
                                }
                            }else{
                                array_push($servicesArray, array('serviceId' => $servicePlan->service_id, 'serviceName' => Service::where('id', $servicePlan->service_id)->first()->name, 'price' => $servicePlan->price));
                            }
                        }
                    }
                }
                /*###### finding services that work with province ######*/
                foreach($serviceIds as $serviceId){
                    $servicePlans = ServicePlan::where([['service_id', '=', $serviceId], ['province_id', '=', $provinceId]]);
                    if($servicePlans != null){
                        $servicePlans = $servicePlans->get();
                        $found = false;
                        foreach($servicePlans as $servicePlan){
                            if($servicePlan->min_weight !== null && $servicePlan->max_weight !== null){
                                if($servicePlan->min_weight < $weight && $weight <= $servicePlan->max_weight){
                                    $found = true;
                                    array_push($servicesArray, array('serviceId' => $servicePlan->service_id, 'serviceName' => Service::where('id', $servicePlan->service_id)->first()->name, 'price' => $servicePlan->price));
                                }
                            }else{
                                $found = true;
                                array_push($servicesArray, array('serviceId' => $servicePlan->service_id, 'serviceName' => Service::where('id', $servicePlan->servcie_id)->first()->name, 'price' => $servicePlan->price));
                            }
                        }
                        if(!$found){
                            $maxServicePlan = ServicePlan::where([['province_id', '=', $provinceId], ['service_id', '=', $serviceId]])->orderBy('max_weight', 'DESC')->first();
                            if($maxServicePlan){
                                if($maxServicePlan->service_id == 3){
                                    if($weight%1000 == 0){
                                        $weight--;
                                    }
                                    $weight = (floor($weight/1000) * 1000) + 1000;
                                    $multiple = ($weight - $maxServicePlan->max_weight)/1000;
                                    $price = $maxServicePlan->price + ($multiple * 2500);
                                    array_push($servicesArray, array('serviceId' => $maxServicePlan->service_id, 'serviceName' => Service::where('id', $servicePlan->service_id)->first()->name, 'price' => $price));
                                }
                            }
                        }
                    }
                }
                echo json_encode($servicesArray);
            }else{
                echo json_encode(array('error' => "city not found"));
            }
        }else{
            echo json_encode(array('error' => 'not enough parameter'));
        }
    }

    public function addDelivery(Request $request){
        $params = $request->all();
        if(isset($params['serviceId']) && ($params['serviceId'] == 1 || $params['serviceId'] == 2)){
            if(isset($params['serviceId']) && isset($params['cityId']) && isset($params['companyTrackingCode']) && isset($params['address']) && isset($params['fullName']) && isset($params['deliveryType']) && isset($params['shift']) && isset($params['parcelType']) && isset($params['sendDate'])){
                $serviceId = $params['serviceId'];
                $cityId = $params['cityId'];
                $companyTrackingCode = $params['companyTrackingCode'];
                $address = $params['address'];
                $fullName = $params['fullName'];
                $deliveryType = $params['deliveryType'];
                $shift = $params['shift'];
                $parcelType = $params['parcelType'];
                $sendDate = $params['sendDate'];

                $city = City::where('id', $cityId)->first();
                if($city != null){
                    $service = ServiceCity::where('city_id', $cityId)->where('service_id', $serviceId)->first();
                    if($service != null){
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "http://api.linkexpress.ir/v1/order/add");
                        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                        curl_setopt($ch, CURLOPT_USERPWD, "honarbakhshan:honar4130");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, 'companyTrackingCode=' . $companyTrackingCode. '&address=' . $address . '&fullName=' . $fullName . '&deliveryType=' . $deliveryType . '&shift=' . $shift . '&parcelType=' . $parcelType . '&sendDate=' . $sendDate . '&city=' . 'شهریار');
                        $result = curl_exec($ch);
                        curl_close($ch);
                        $r = json_decode($result, true);
                        if(isset($r['code'])){
                            echo $result;
                        }else{
                            echo json_encode(array('error' => 'something is wrong'));
                        }
                    }else{
                        echo json_encode(array('error' => 'service not found'));
                    }
                }else{
                    echo json_encode(array('error' => 'city not found'));
                }
            }else{
                echo json_encode(array('error' => 'not enough parameter'));
            }
        }else{
            echo json_encode(array('error' => 'service not available'));
        }
    }

    public function getDeliveryStatus(Request $request){

        $params = $request->all();

        if(isset($params['serviceId']) && isset($params['trackingCode'])){
            $serviceId = $params['serviceId'];
            $trackingCode = $params['trackingCode'];
            if($serviceId == 1 || $serviceId == 2){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://api.linkexpress.ir/v1/order/status");
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "honarbakhshan:honar4130");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, 'trackingCode='. $trackingCode);
                $result = curl_exec($ch);
                curl_close($ch);
                $resultJson = json_decode($result, true);
                if(isset($resultJson['result'])){
                    $statusCode = $resultJson['result']['state'];
                    $message = '';
                    if($statusCode == 1){
                        $message = 'submitted';
                    }else if($statusCode == 2){
                        $message = 'rejected';
                    }else if($statusCode == 3){
                        $message = 'canceled';
                    }else if($statusCode == 4){
                        $message = 'approved';
                    }else if($statusCode == 5){
                        $message = 'courier';
                    }else if($statusCode == 6){
                        $message = 'customer';
                    }else if($statusCode == 7){
                        $message = 'failed';
                    }else{
                        $message = 'unknown';
                    }
                }else{
                    if($resultJson['code'] == '2'){
                        $message = 'not found';
                    }else{
                        $message = 'unknown';
                    }
                }
                $responseArray = array('message' => $message);
                echo json_encode($responseArray);
            }else{
                echo json_encode(array('error' => 'service not defined'));
            }
        }else{
            echo json_encode(array('error' => 'not enough parameter'));
        }
    }

    public function cancelDelivery(Request $request){
        $params = $request->all();
        if(isset($params['serviceId']) && isset($params['trackingCode'])){
            $serviceId = $params['serviceId'];
            $trackingCode = $params['trackingCode'];
            if($serviceId == 1 || $serviceId == 2){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://api.linkexpress.ir/v1/order/cancel");
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "honarbakhshan:honar4130");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, 'trackingCode=' . $trackingCode);
                $result = curl_exec($ch);
                curl_close($ch);
                $resultJson = json_decode($result, true);
                $resultCode = $resultJson['code'];
                $message = '';
                if($resultCode == 0){
                    $message = 'done';
                }else if($resultCode == 1){
                    $message = 'wrong parameter';
                }else if($resultCode == 2){ 
                    $message = 'not found';
                }else if($resultCode == 3){
                    $message = 'unknown error';
                }else if($resultCode == 4){
                    $message = 'uncancellable';
                }else{
                    $message = 'unknown';
                }
                $responseArray = array('message' => $message);
                echo json_encode($responseArray);
            }else{
                echo json_encode(array('error' => 'service not defined'));
            }
        }else{
            echo json_encode(array('error' => 'not enough parameter'));
        }
    }
}
