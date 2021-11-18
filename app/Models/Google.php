<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Google extends Model
{
    use HasFactory;

    public function validate () {
        $hash = request('receipt');

        if (strlen($hash) != 64) return ['status' => false, 'error' => 'invalid'];
        if (hexdec(substr($hash, -1)) % 2 === 0) return ['status' => false, 'error' => 'invalid'];
        if (hexdec(substr($hash, -2)) % 6 === 0) return ['status' => false, 'error' => 'limit'];

        $date = new \DateTime('+1 month', new \DateTimeZone('UTC'));
        $date->setTimezone(new \DateTimeZone('-06:00'));
        
        return ['status' => true, 'expiry_time' => $date->format('c')];
    }
}
