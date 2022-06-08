<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionCat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{

    public function cats_list()
    {
        $model=QuestionCat::paginate(10);

        return Response()->json($model,200);

    }
    public function cats_update(Request $request)
    {
        $inputs=$request->only([
            "title",
            "status",
        ]);
        $sample = QuestionCat::where('id', $request->id)->update($inputs);
        return Response()->json($sample);
    }  public function get_answer_item()
    {
        $model = DB::table('questions')
            ->join('question_cats', 'question_cats.id', '=', 'questions.question_cats_id')
            ->select('questions.*', 'question_cats.title')
            ->paginate(5);
        return Response()->json($model);
    }
    public function add_cat(Request $request)
    {
        $inputs=$request->only([
            "title",
            "status",
        ]);
        $model =QuestionCat::create($inputs);


        return Response()->json($model);
    }

    public function get_select_answer($id)
    {
        $sample = Question::find($id);
        return Response()->json($sample);
    }
    public function get_cat_list()
    {
        $model=QuestionCat::all();

        return Response()->json($model,200);

    }
    public function add_answer(Request $request)
    {
        $inputs=$request->only([
            'question','answer','top','status','question_cats_id','short_answer'
        ]);
        $model =Question::create($inputs);
        return Response()->json($model);
    }
    public function update_answer(Request $request)
    {
        $inputs=$request->only([
            'question','answer','top','status','question_cats_id','short_answer'
        ]);
        $sample = Question::where('id', $request->id)->update($inputs);
        return Response()->json($sample);
    }
}
