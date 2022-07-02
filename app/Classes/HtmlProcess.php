<?php

namespace App\Classes;

class HtmlProcess
{
    public function GetImgSrc($text)
    {
        if($text!=null)
        {
            $arr=[];
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);

            $doc->loadHTML($text);
            libxml_clear_errors();
            foreach ($doc->getElementsByTagName('img') as $img) {
                $name=explode("/",$img->getAttribute('src'));
                $arr[]= end($name);
            }
            return $arr;
        }
    }
}

