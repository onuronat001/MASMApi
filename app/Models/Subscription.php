<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Subscription extends Model
{
    use HasFactory;

    public function create () {
        foreach (['client_token', 'receipt'] as $param) {
            if (! request($param)) {
                return response()->json(['error' => sprintf ('%s parameter is required!', $param)]);
            }
        }

        $device = Device::where('client_token', '=', request('client_token'));
        if (! $device->exists()) {
            return response()->json(['error' => 'Device not registered!']);
        }
        
        $subscription = self::where('device_id', '=', $device->first()->id);

        if ($subscription->exists()) {
            return response()->json(['error' => 'Subscription is exists!']);
        }

        $app = App::where('id', '=', $device->first()->appId)->first();
        if ($device->first()->os == 'iOs') {
            $result = Http::withBasicAuth($app->ios_username, $app->ios_password)->post('http://MASMApi.dev/api/mock/ios', ['receipt' => request('receipt')]);

            if (! $result['status']) {
                return response()->json(['error' => 'Receipt is invalid - by iOs Mock!']);
            }
        }
        else {
            $result = Http::withBasicAuth($app->google_username, $app->google_password)->post('http://MASMApi.dev/api/mock/google', ['receipt' => request('receipt')]);

            if (! $result['status']) {
                return response()->json(['error' => 'Receipt is invalid - by Google Mock!']);
            }
        }
        
        $expiry_time = new \DateTime($result['expiry_time']);
        $expiry_time->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        self::insert([
            'device_id'     => $device->first()->id,
            'expiry_time'   => $expiry_time->format('Y-m-d H:i:s'),
            'receipt'       => request('receipt')
        ]);

        Http::post($app->callback_endpoint, [
            'appId'     => $app->id,
            'deviceId'  => $device->first()->uid,
            'event'     => 'started'
        ]);

        return response()->json(['Purchase' => 'OK']);
    }

    public function check () {
        foreach (['client_token'] as $param) {
            if (! request($param)) {
                return response()->json(['error' => sprintf ('%s parameter is required!', $param)]);
            }
        }

        $device = Device::where('client_token', '=', request('client_token'));

        $subscription = self::where('device_id', '=', $device->first()->id);

        return response()->json(['status' => $subscription->exists() && $subscription->first()->status == 1]);
    }
}
