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

class ArtController extends Controller
{
    /*public function getActiveCategories(Request $request){
        $categoriesInformation = DB::select(
            "SELECT id, `name`, `url` FROM category WHERE hide = 0 ORDER BY `name` ASC "
        );
        if(count($categoriesInformation) == 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any active category', 'umessage' => 'موردی یافت نشد', 'categories' => []));
            exit();
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'categories successfully found', 'categories' => $categoriesInformation));
    }*/


    public function activeArts(Request $request){
        $artsInformation = DB::select(
            "SELECT A.id, A.artName AS `name`, AP.url_fa AS url  
            FROM arts A 
            INNER JOIN art_page AP ON A.id = AP.art_id 
            ORDER BY A.artName ASC
            "
        );
        if(count($artsInformation) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any active art', 'umessage' => 'هنری یافت نشد', 'arts' => []));
            exit();
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'arts successfully found', 'arts' => $artsInformation));   
    } 
    
    public function addArtCourseLink(Request $request){
        if(!isset($request->artId) || !isset($request->firstCourseId) || !isset($request->secondCourseId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        
        $artId = $request->artId;
        $firstCourseId = $request->firstCourseId;
        $secondCourseId = $request->secondCourseId;

        $art = DB::select("SELECT id FROM arts WHERE id = $artId LIMIT 1");

        if(count($art) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'art not found', 'umessage' => 'هنر یافت نشد'));
            exit();
        }

        $time = time();

        $firstQueryResult = DB::insert(
            "INSERT INTO art_courses (
                art_id, course_id, `date`
            ) VALUES (
                $artId, $firstCourseId, $time
            )"
        );

        $secondQueryResult = DB::insert(
            "INSERT INTO art_courses (
                art_id, course_id, `date`
            ) VALUES (
                $artId, $secondCourseId, $time
            )"
        );

        if(!$firstQueryResult || !$secondQueryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'q', 'message' => 'error while inserting a new link', 'umessage' => 'خطا هنگام ذخیره‌سازی لینک جدید'));
        }else{
            echo json_encode(array('status' => 'done', 'message' => 'link successfully inserted'));
        }
    }

    public function filteredPaginatedLinkedArts(Request $request){
        if(!isset($request->page) || !isset($request->artId) || !isset($request->maxCount)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        $page = $request->page;
        $artId = $request->artId;
        $maxCount = $request->maxCount;

        $artQuery = '';

        if($artId !== 0){
            $artQuery = " AND C.id = $artId ";
        }
        $arts = DB::select(
            "SELECT 
                A.id AS artId, 
                A.artName 
            FROM arts A 
            WHERE A.id IN (
                SELECT DISTINCT AC.art_id 
                FROM art_courses AC 
            )
            $artQuery 
            ORDER BY A.artName ASC "
        );
        if(count($arts) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'could not find any art', 'links' => [], 'page' => 0));
            exit();
        }
        $count = count($arts);
        $response = [];
        for($i=($page - 1)*$maxCount ; $i<count($arts) && count($response) < $maxCount; $i++){
            array_push($response, $arts[$i]);
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'arts successfully found', 'links' => $response, 'page' => ($count / $maxCount)));
    }


    public function unlinkedArts(Request $request){
        $arts = DB::select(
            "SELECT A.id AS artId, A.artName 
            FROM arts A 
            WHERE A.id NOT IN ( 
                SELECT DISTINCT AC.art_id 
                FROM art_courses AC  
            ) 
            ORDER BY A.artName ASC "
        );

        if(count($arts) == 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any left art', 'umessage' => 'تمام هنرها استفاده شده اند'));
            exit();
        }

        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'arts successfully found', 'arts' => $arts));
    }

    public function artsLinkedCourses(Request $request){
        if(!isset($request->artId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough information', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        $artId = $request->artId;

        $courses = DB::select(
            "SELECT course_id 
            FROM art_courses 
            WHERE art_id = $artId 
            ORDER BY course_id ASC "
        );

        if(count($courses) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'this art does not have any course linked to it', 'umessage' => 'این هنر هیچ لینکی ندارد', 'courses' => []));
            exit();
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'linked courses successfully found', 'courses' => $courses));
    }

    public function addCourseToArt(Request $request){
        if(!isset($request->courseId) || !isset($request->artId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        $artId = $request->artId;
        $courseId = $request->courseId;

        $record = DB::select(
            "SELECT id FROM art_courses WHERE art_id = $artId AND course_id = $courseId LIMIT 1 "
        );

        if(count($record) !== 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'this link already exists', 'umessage' => 'لینک وجوددارد و تکراری است'));
            exit();
        }

        $time = time();

        $queryResult = DB::select(
            "INSERT INTO art_courses (
                art_id, course_id, `date`  
            ) VALUES (
                $artId, $courseId, $time 
            )"
        );

        /*if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'q', 'message' => 'could not insert a new rocond to the database', 'umessage' => 'خطا در ذخیره اطلاعات جدید'));
            exit();
        }*/
        echo json_encode(array('status' => 'done', 'message' => 'link successfully added', 'courseId' => $courseId));
    }

    public function removeArtLinks(Request $request){
        if(!isset($request->artId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough paramter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        $artId = $request->artId;
        
        DB::delete(
            "DELETE FROM art_courses 
            WHERE art_id = $artId "
        );

        echo json_encode(array('status' => 'done', 'message' => 'all links successfully removed'));
    }

    public function removeLink(Request $request){
        if(!isset($request->artId) || !isset($request->courseId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough paramter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        $artId = $request->artId;
        $courseId = $request->courseId;

        DB::delete(
            "DELETE FROM art_courses 
            WHERE art_id = $artId AND course_id = $courseId "
        );

        echo json_encode(array('status' => 'done', 'message' => 'link successfully removed'));  
    }
    
}
