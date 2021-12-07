<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawyerAvailability extends Model
{
    use HasFactory;

    protected $table = 'lawyersavailability';
    public $timestamps = false;
}
