<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use App\Models\Province;

class City extends Model
{
    use HasFactory;

    protected $table = 'users';
    protected $primaryKey = 'id';
    
    public $timestamps = false;

    public function province(){
        return $this->hasOne(Province::class, 'id', 'parent_province');
    }

    public function services(){
        return $this->hasMany(ServiceCity::class, 'city_id', 'id');
    }

}