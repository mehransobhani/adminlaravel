<?php

namespace App\Repository;

use App\Models\QuestionCat;
use App\Classes\check_isset;

abstract class Repository implements  RepositoryInterface
{

    private $model;

    public function __construct()
    {
        $this->model=app($this->model());
    }
    abstract public function model();
    public function get_by_id($id)
    {
        $isset=new check_isset($this->model,$id);
        if($isset->check())
            return response()->json($this->model->findOrFail($id),200);
        return Response()->json('bad request', 400);
    }

    public function get_list()
    {
        return Response()->json($this->model->all(), 200);

    }

    public function create(array $data)
    {

            return Response()->json($this->model->create($data),200);

    }

    public function update(array $data ,$id)
    {
        $isset=new check_isset($this->model,$id);
        if($isset->check())
        {
            try {
                return Response()->json($this->model->where("id",$id)->update($data),200);
            }
            catch (\Exception $e)
            {
                return Response()->json('error',500);
            }
        }
        return Response()->json('bad request',400);
    }

    public function get_all()
    {

        return Response()->json($this->model::paginate(10),200);
    }
}

