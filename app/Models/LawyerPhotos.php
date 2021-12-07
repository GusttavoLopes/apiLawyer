<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawyerPhotos extends Model
{
    use HasFactory;

    protected $table = 'lawyersphotos';
    public $timestamps = false;
}
