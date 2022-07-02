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

class CategoryController extends Controller
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


    public function getActiveCategories(Request $request){
        $categoriesInformation = DB::select(
            "SELECT C.id, C.name, C.url 
            FROM category C 
	    WHERE C.hide = 0 
	    ORDER BY C.name ASC "
        );
        if(count($categoriesInformation) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any active category', 'umessage' => 'موردی یافت نشد', 'categories' => []));
            exit();
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'categories successfully found', 'categories' => $categoriesInformation));
    } 

    public function filteredPaginatedCategoryCourse(Request $request){
        if(!isset($request->page) || !isset($request->maxCount) || !isset($request->categoryId) || !isset($request->courseId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough paramter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        
        $page = $request->page;
        $maxCount = $request->maxCount;
        $categoryId = $request->categoryId;
        $courseId = $request->courseId;

        $categoryQuery = '';
        $courseQuery = '';

        if($categoryId !== 0){
            $categoryQuery = " AND CC.category_id = $categoryId ";
        }
        if($courseId !== 0){
            $courseId = " AND CC.course_id = $courseId ";
        }

        $categoryCourses = DB::select(
            "SELECT DISTINCT  
                CC.category_id AS categoryId, 
                C.name AS categoryName 
            FROM category_courses CC 
            INNER JOIN category C ON C.id = CC.category_id
            WHERE C.parentID = 0 
                $categoryQuery 
                $courseQuery 
            ORDER BY C.name ASC "
        );
        if(count($categoryCourses) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'link not found', 'links' => [], 'count' => 0));
            exit();
        }
        
        $count = count($categoryCourses);
        $links = [];
        for($i=($page - 1) * $maxCount; $i<$count && count($links) < $maxCount; $i++){
            $info = $categoryCourses[$i];
            $info->courses = [];
            $infoCourses = DB::select(
                "SELECT course_id 
                FROM category_courses 
                WHERE category_id = $info->categoryId 
                ORDER BY id DESC "
            );
            if(count($infoCourses) !== 0){
                foreach($infoCourses as $ic){
                    array_push($info->courses, $ic->course_id);
                }
            }
            array_push($links, $info);
        }
        if(count($links) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'link not found', 'links' => [], 'count' => $count));
        }else{
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'links successfully found', 'links' => $links, 'count' => $count));
        }
    }

    public function addCategoryCourseLink(Request $request){
        if(!isset($request->categoryId) || !isset($request->firstCourseId) || !isset($request->secondCourseId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        
        $categoryId = $request->categoryId;
        $firstCourseId = $request->firstCourseId;
        $secondCourseId = $request->secondCourseId;

        $category = DB::select("SELECT parentID FROM category WHERE id = $categoryId LIMIT 1");

        if(count($category) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'category not found', 'umessage' => 'دسته‌بندی یافت نشد'));
            exit();
        }

        $time = time();

        $firstQueryResult = DB::insert(
            "INSERT INTO category_courses (
                category_id, course_id, `date`
            ) VALUES (
                $categoryId, $firstCourseId, $time
            )"
        );

        $secondQueryResult = DB::insert(
            "INSERT INTO category_courses (
                category_id, course_id, `date`
            ) VALUES (
                $categoryId, $secondCourseId, $time
            )"
        );

        if(!$firstQueryResult || !$secondQueryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'q', 'message' => 'error while inserting a new linux', 'umessage' => 'خطا هنگام ذخیره‌سازی لینک جدید'));
        }else{
            echo json_encode(array('status' => 'done', 'message' => 'link successfully inserted'));
        }
    }

    public function removeCourseFromCategory(Request $request){
        if(!isset($request->linkId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        $linkId = $request->linkId;

        $queryResult = DB::delete(
            "DELETE FROM category_courses 
            WHERE id = $linkId"
        );

        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'q', 'message' => 'an error occured while deleting the link', 'umessage' => 'خطا هنگام حذف لینک'));
        }else{
            echo json_encode(array('status' => 'done', 'message' => 'link successfully deleted'));
        }
    } 

    public function filteredPaginatedLinkedCategories(Request $request){
        if(!isset($request->page) || !isset($request->categoryId) || !isset($request->maxCount)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        $page = $request->page;
        $categoryId = $request->categoryId;
        $maxCount = $request->maxCount;

        $categoryQuery = '';

        if($categoryId !== 0){
            $categoryId = " AND C.id = $categoryId ";
        }
        $categories = DB::select(
            "SELECT 
                C.id AS categoryId, 
                C.name AS categoryName 
            FROM category C 
            WHERE C.id NOT IN (
                SELECT DISTINCT CC.category_id 
                FROM category_courses CC 
            )
            AND C.hide = 0  
            AND parentID = 0 
            $categoryQuery 
            ORDER BY C.name ASC "
        );
        if(count($categories) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'could not find any category', 'categories' => [], 'page' => 0));
            exit();
        }
        $count = count($categories);
        $response = [];
        for($i=($page - 1)*$maxCount ; $i<count($categories) && count($response) < $maxCount; $i++){
            array_push($response, $categories[$i]);
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'categories successfully found', 'categories' => $response, 'page' => ($count / $maxCount)));
    }

    public function unlinkedRootCategories(Request $request){
        $categories = DB::select(
            "SELECT C.id AS categoryId, C.name AS categoryName 
            FROM category C 
            WHERE C.id NOT IN ( 
                SELECT DISTINCT CC.category_id 
                FROM category_courses CC 
                ORDER BY CC.id ASC 
            ) 
            AND C.parentID = 0 
            AND C.hide = 0  
            ORDER BY C.name ASC "
        );

        if(count($categories) == 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any left category', 'umessage' => 'تمام دسته‌بندی ها استفاده شده اند'));
            exit();
        }

        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'categories successfully found', 'categories' => $categories));
    }
}
