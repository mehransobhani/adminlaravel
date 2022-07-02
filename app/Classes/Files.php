<?php

namespace App\Classes;



 use Illuminate\Support\Facades\File;

class Files
{

    public function upload($path,$request)
    {

        $file_type=new UploadFileType($request["name"]);
        $temp=$file_type->get_type();
        $valid =$file_type->type_validation(["jpg","png","jpeg"]);
        if($valid)
        {
            $filename=time().".".$temp;
             move_uploaded_file($request["tmp_name"],base_path()."/public/".$path.$filename);

            return  response()->json($path.$filename,200);


        }
        else{
            return response()->json("error_type_validation",500);
        }

    }

    public function delete($path,$files)
    {

            File::delete($path.$files);

    }
}


