<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePlan extends Model
{
    use HasFactory;

    protected $table = 'service_plans';
    protected $primaryKey = 'id';
    
    public $timestamps = false;

}
