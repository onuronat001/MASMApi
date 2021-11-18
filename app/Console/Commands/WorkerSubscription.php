<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\App;
use App\Models\Device;
use App\Models\Subscription;

class WorkerSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscriptions Check Worker';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $subs = Subscription::where('expiry_time', '<', \DB::raw('NOW()'))->get();
        foreach ($subs as $sub) {
            $device = Device::where ('id', '=', $sub->device_id)->first();
            $app = App::where('id', '=', $device->appId)->first();
            if ($device->os == 'iOs') {
                $result = Http::withBasicAuth($device->ios_username, $device->ios_password)->post('http://MASMApi.dev/api/mock/ios', ['receipt' => $sub->receipt]);
                if (! $result['status']) {
                    if ($result['error'] == 'limit') continue;
                    Subscription::where('id', '=', $sub->id)->update(['status' => 0]);
                    Http::post($app->callback_endpoint, [
                        'appId'     => $app->id,
                        'deviceId'  => $device->uid,
                        'event'     => 'cancelled'
                    ]);
                    continue;
                }
            }
            else {

                $result = Http::withBasicAuth($device->google_username, $device->google_password)->post('http://MASMApi.dev/api/mock/google', ['receipt' => $sub->receipt]);
                if (! $result['status']) {
                    if ($result['error'] == 'limit') continue;
                    Subscription::where('id', '=', $sub->id)->update(['status' => 0]);
                    Http::post($app->callback_endpoint, [
                        'appId'     => $app->id,
                        'deviceId'  => $device->uid,
                        'event'     => 'cancelled'
                    ]);
                    continue;
                }
            }

            $expiry_time = new \DateTime($result['expiry_time']);
            $expiry_time->setTimezone(new \DateTimeZone(date_default_timezone_get()));

            Subscription::where('id', '=', $sub->id)->update(['renew_count' => \DB::raw('renew_count + 1'), 'expiry_time' => $expiry_time->format('Y-m-d H:i:s')]);

            Http::post($app->callback_endpoint, [
                'appId'     => $app->id,
                'deviceId'  => $device->uid,
                'event'     => 'renewed'
            ]);
        }
        return Command::SUCCESS;
    }
}
