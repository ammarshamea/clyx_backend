<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'name_ar', 'slug', 'email', 'phone', 'logo',
        'db_driver', 'db_host', 'db_port', 'db_database',
        'db_username', 'db_password', 'status', 'meta',
    ];

    protected $hidden = ['db_password'];

    protected $casts = [
        'meta' => 'array',
    ];

    // Encrypt sensitive DB credentials
    public function setDbHostAttribute($value): void
    {
        $this->attributes['db_host'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDbHostAttribute($value): ?string
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception) {
            return $value;
        }
    }

    public function setDbDatabaseAttribute($value): void
    {
        $this->attributes['db_database'] = Crypt::encryptString($value);
    }

    public function getDbDatabaseAttribute($value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    public function setDbUsernameAttribute($value): void
    {
        $this->attributes['db_username'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDbUsernameAttribute($value): ?string
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception) {
            return $value;
        }
    }

    public function setDbPasswordAttribute($value): void
    {
        $this->attributes['db_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDbPasswordAttribute($value): ?string
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception) {
            return $value;
        }
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest();
    }

    public function getTenantConnectionName(): string
    {
        return 'tenant_' . $this->slug;
    }
}
