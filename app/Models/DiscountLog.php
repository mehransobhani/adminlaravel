<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use App\Models\Province;

class DiscountLog extends Model
{
    use HasFactory;

    protected $table = 'discount_logs';
    protected $primaryKey = 'id';
    
    public $timestamps = false;

}
