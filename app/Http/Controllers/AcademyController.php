<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class AcademyController extends Controller
{

    private static $ACADEMY_URL = 'https://academy.honari.com/api/shop/courses';

    public function allCourses(Request $request){
        $ch = curl_init(self::$ACADEMY_URL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        echo $result;
    }
}