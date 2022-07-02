<?php

namespace App\Http\Controllers;

use App\Classes\Files;
use http\Env\Response;

class FilesController extends Controller
{
   private $files;
    public function __construct(Files $files)
    {
        $this->files=$files;
    }

    public function upload()
    {
       $file=$_FILES["image"];
       return  $this->files->upload("/Ckeditor/Faq/",$file);
     }
    public function delete(array $arr)
    {
        return $this->files->delete($arr);
    }
}

