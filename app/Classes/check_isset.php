<?php

namespace App\Classes;

class check_isset
{
private $model;
private $id;

    public function __construct($model , $id)
    {
        $this->model=$model;
        $this->id=$id;
    }

      public function check()
    {
        try {
            $exist=$this->model->findOrFail($this->id);
            if($exist)
                return true;
            return false;

        }
        catch (\Exception $e)
        {
            return false;
        }

    }
}

