<?php

namespace App\Classes\GetAllSrc;

use App\Classes\HtmlProcess;

class FaqAnswerAllSrc implements GetAllSrcInterface
{
    public function get($model)
    {
        $answer_src = [];
        foreach ($model->original as $item) {
            $answer_src[] = $item->answer;
        }
        $factory = new HtmlProcess();
        $src = [];
        foreach ($answer_src as $item) {
            if ($item != null) {
                $src_img = $factory->GetImgSrc($item);
                if ($src_img != null) {
                    foreach ($src_img as $srcs) {
                        if ($srcs != "") {
                            $src[] = $srcs;
                        }
                    }
                }
            }
        }
        return $src;
    }
}

