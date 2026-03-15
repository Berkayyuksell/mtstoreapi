<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'WareHouseCode',
        'WareHouseName',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
