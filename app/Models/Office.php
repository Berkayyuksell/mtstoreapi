<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'OfficeCode',
        'OfficeName',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
