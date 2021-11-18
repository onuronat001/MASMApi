<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{

    use HasFactory;

    public function create () {
        foreach (['uid', 'appId', 'os', 'language'] as $param) {
            if (! request($param)) {
                return response()->json(['error' => sprintf ('%s parameter is required!', $param)]);
            }
        }

        if (! App::where('id', '=', request('appId'))->exists()) {
            return response()->json(['error' => 'appId is invalid!']);
        }
        
        $device = self::where([
            'uid'   => request('uid'),
            'appId' => request('appId'),
            'os'    => request('os'),
        ]);

        if (! $device->exists()) {
            $client_token = hash ('sha256', uniqid());
            self::insert([
                'uid'           => request('uid'),
                'appId'         => request('appId'),
                'client_token'  => $client_token,
                'language'      => request('language'),
                'os'            => request('os')
            ]);
        }
        else {
            $client_token = $device->first()->client_token;
        }

        return response()->json([
            'register' => 'OK',
            'client_token' => $client_token
        ]);
    }

}
