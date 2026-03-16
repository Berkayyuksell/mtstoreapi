<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'project_id',
        'company_id',
        'office_id',
        'store_id',
        'warehouse_id',
        'price_group_code',
        'disc_price_group_code',
        'zpl_label_template_id',
        'name',
        'email',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zplLabelTemplate()
    {
        return $this->belongsTo(ZplLabelTemplate::class);
    }

    /**
     * Kullanıcının aktif ZPL fiyat grup kodunu döner.
     * Öncelik: user → store → fallback sabit değer
     */
    public function resolvedPriceGroupCode(): string
    {
        return $this->price_group_code
            ?? $this->store?->price_group_code
            ?? 'PSF';
    }

    public function resolvedDiscPriceGroupCode(): string
    {
        return $this->disc_price_group_code
            ?? $this->store?->disc_price_group_code
            ?? 'PSF_IND';
    }

    /**
     * Kullanıcıya atanmış template; yoksa projenin varsayılanı döner.
     */
    public function resolvedZplTemplate(): ?\App\Models\ZplLabelTemplate
    {
        if ($this->zpl_label_template_id) {
            return $this->zplLabelTemplate;
        }

        return \App\Models\ZplLabelTemplate::defaultForProject($this->project_id);
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class);
    }
}
