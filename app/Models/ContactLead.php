<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactLead extends Model
{
    protected $fillable = [
        'name', 'email', 'company', 'message', 'status', 'ip_address',
    ];
}
