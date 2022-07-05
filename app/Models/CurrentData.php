<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentData extends Model
{
    use HasFactory;
    protected $table = 'current_data';
    public $timestamps = false;
    protected $guarded = [];
}
