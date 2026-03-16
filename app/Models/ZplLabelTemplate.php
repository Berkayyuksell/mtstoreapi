<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ZplLabelTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'template_code',
        'template_name',
        'zpl_template',
        'variables',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'zpl_label_template_id');
    }

    /**
     * Kullanıcının projesine göre varsayılan template'i döner.
     * project_id eşleşen varsa onu, yoksa global (NULL) olanı tercih eder.
     */
    public static function defaultForProject(int $projectId): ?self
    {
        return self::where('template_code', 'default')
            ->where('is_active', true)
            ->where(function ($q) use ($projectId) {
                $q->where('project_id', $projectId)
                  ->orWhereNull('project_id');
            })
            ->orderByRaw('project_id IS NULL ASC') // proje özelini önce getir
            ->first();
    }
}
