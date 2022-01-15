<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use App\Models\Province;

class Discount extends Model
{
    use HasFactory;

    protected $table = 'discounts';
    protected $primaryKey = 'id';
    
    public $timestamps = false;

    public function users(){
        return $this->hasMany(DiscountDependency::class, 'discount_id', 'id')->where('dependency_type', 'user');
    }

    public function products(){
        return $this->hasMany(DiscountDependency::class, 'discount_id', 'id')->where('dependency_type', 'product');
    }

    public function categories(){
        return $this->hasMany(DiscountDependency::class, 'discount_id', 'id')->where('dependency_type', 'category');
    }

    public function provinces(){
        return $this->hasMany(DiscountDependency::class, 'discount_id', 'id')->where('dependency_type', 'province');
    }
}
