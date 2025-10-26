<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    // role field in roles table permission field in permissions table
    protected $fillable = [
        'name'
    ];
}
