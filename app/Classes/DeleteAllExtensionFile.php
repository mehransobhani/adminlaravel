<?php

namespace App\Classes;
class DeleteAllExtensionFile
{

    public function delete($arr , $path)
    {
         foreach (glob(base_path() . $path) as $file) {
            $filename=explode("/",$file);
            if (!in_array(end($filename),$arr)) {
                unlink($file);
              }
          }
     }

}

