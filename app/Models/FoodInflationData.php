<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodInflationData extends Model
{
    use HasFactory;
    protected $table = 'food_inflation';
    public $timestamps = false;
    protected $guarded = [];

    public function monthdetail()
    {
        return $this->belongsTo(Month::class, 'month_id');
    }
    public function yeardetail()
    {
        return $this->belongsTo(Year::class, 'year_id');
    }}
