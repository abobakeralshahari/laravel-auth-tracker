<?php


namespace Alshahari\AuthTracker\Middleware;


use Alshahari\AuthTracker\Services\DeviceService;
use Closure;
use Illuminate\Validation\UnauthorizedException;
use Jenssegers\Agent\Agent;


class StoreDevice
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param null $guard
     * @param bool $isRequired
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null, $isRequired = true)
    {


       // $deviceUdid = $request->header('X-Device-UDID');
        $deviceUdid = $request->header('x-device-udid');
//        if(empty($deviceUdid) || $deviceUdid == null){
//            $deviceUdid = $request->header('X-Device-UDID');
//        }

        if (empty($deviceUdid) && ! $isRequired) {

//            if ($request->is('api/*')) {
//                // return JSON-formatted response
//               //  We continue on
//                  return $next($request);
//            } else {

                $rep = new DeviceService();
                $rep->setHeader();
//                $deviceUdid=$rep->getDeviceId();
            $device= $rep->saveDevice();
            $deviceUdid=$rep->getDeviceId();
         //   }

            // We continue on
           // return $next($request);
        }

        if (empty($deviceUdid) && $isRequired) {
            throw new UnauthorizedException('You need to specify your device details.');
        }


        //
//        'os' => $request->header('X-Device-OS'),
//            'os_version' => $request->header('X-Device-OS-Version'),
//            'manufacturer' => $request->header('X-Device-Manufacturer'),
//            'model' => $request->header('X-Device-Model'),
//            'fcm_token' => $request->header('X-Device-FCM-Token'),
//            'app_version' => $request->header('X-Device-App-Version'),


//        x-device-fcm-token: c4o-OWYcQ_yS0v60K1gf33:APA91bGPAMEh86zWtPIub1mlOKMQCk784Qn6U-oU7AAkKpphSOmf5p0EQf6Sbn6sROVssS2VB5v6r3oACA-GbN834fm5vtRqqaMNvRs8WwBeB8En86K5T3DIcXHv-5F87wiAU3BTqWPX
//I/flutter (28432): X-localization: ar
//I/flutter (28432): country-code: ye
//I/flutter (28432): x-device-app-version: 1.0.17
//I/flutter (28432): User-Agent: samsung - SM-N960U - android - 10 - QP1A.190711.020
//I/flutter (28432): x-device-manufacturer: samsung
//I/flutter (28432): x-device-model: SM-N960U
//I/flutter (28432): x-device-os: android
//I/flutter (28432): x-device-os-version: 10
//I/flutter (28432): x-device-udid: QP1A.190711.020
//I/flutter (28432): unix-timestamp: 1689419312
//I/flutter (28432): time-zone-name: +03
//I/flutter (28432): time-zone-offset: 3:00:00.000000


        // We save the device details
        $device = app(config('auth_tracker.device_model'))->query()->updateOrCreate([
            'udid' => $deviceUdid,
        ], [
            'udid' => $deviceUdid,
            'os' => $request->header('x-device-os'),
            'os_version' => $request->header('x-device-os-version'),
            'manufacturer' => $request->header('x-device-manufacturer'),
            'model' => $request->header('x-device-model'),
            'fcm_token' => $request->header('x-device-fcm-token'),
            'app_version' => $request->header('x-device-app-version'),
            'tenant' => $request->header('country-code'),
        ]);

        $request->device = $device;
        $request->guard = $guard ?? config('auth.defaults.guard');
        
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $user = $request->user($request->guard);

        if (! empty($user) && ! empty($request->device)) {
            $request->device->deviceable()->associate($user);
            $request->device->save();
        }
    }
}
