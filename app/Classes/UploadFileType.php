<?php

namespace App\Classes;

class UploadFileType
{
    private $file_name;
    private $valid=false;

    public function __construct($file_name)
    {
        $this->file_name=$file_name;
    }
    public function get_type()
    {
        $temp = explode(".", $this->file_name);
        return end($temp);
    }
    public function type_validation($types)
    {
        $temp=$this->get_type();
        foreach ($types as $item)
        {
            if(strcmp($item, $temp)==0)
            {
                $this->valid=true;
                return $this->valid;
            }

        }
        return $this->valid;

    }


}

