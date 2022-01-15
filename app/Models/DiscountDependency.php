<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use App\Models\Province;

class DiscountDependency extends Model
{
    use HasFactory;

    protected $table = 'discount_dependencies';
    protected $primaryKey = 'id';
    
    public $timestamps = false;

}
