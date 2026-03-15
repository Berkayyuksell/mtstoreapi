<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'CompanyCode',
        'CompanyName',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function offices()
    {
        return $this->hasMany(Office::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
