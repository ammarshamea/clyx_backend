<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInfo extends Model
{
    protected $table = 'contact_info';

    protected $fillable = ['email', 'phone', 'whatsapp', 'address', 'address_ar'];

    public static function getSingleton(): self
    {
        $row = self::first();
        if (!$row) {
            $row = self::create([
                'email' => 'hello@clyx.agency',
                'phone' => '+966 50 000 0000',
            ]);
        }
        return $row;
    }
}
