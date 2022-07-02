<?php

namespace App\Classes\GetAllSrc;

class GetAllSrcStrategy
{
   private $strategy;
public function __construct($table)
{
if($table=="faq_answer")
{
    $this->strategy=new FaqAnswerAllSrc();
}
}
    public function gets($model)
    {
        return $this->strategy->get($model);
    }
}

