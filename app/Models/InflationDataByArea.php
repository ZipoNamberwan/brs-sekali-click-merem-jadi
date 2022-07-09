<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InflationDataByArea extends Model
{
    use HasFactory;
    protected $table = 'inflation_data_by_area';
    public $timestamps = false;
    protected $guarded = [];

    public function monthdetail()
    {
        return $this->belongsTo(Month::class, 'month_id');
    }
    public function yeardetail()
    {
        return $this->belongsTo(Year::class, 'year_id');
    }
}
