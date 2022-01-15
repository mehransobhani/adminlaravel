<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SP extends Model
{
    use HasFactory;

    protected $table = 'sp';
    protected $primaryKey = 'id';
    
    public $timestamps = false;

}
