<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCity extends Model
{
    use HasFactory;

    protected $table = 'service_cities';
    protected $primaryKey = 'id';
    
    public $timestamps = false;

    public function service(){
        return $this->hasOne(Service::class, 'id', 'service_id');
    }

    public function city(){
        return $this->hasOne(City::class, 'id', 'city_id');
    }
}
