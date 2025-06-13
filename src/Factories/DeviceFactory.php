<?php

namespace Alshahari\AuthTracker\Factories;

use Alshahari\AuthTracker\Parsers\Agent;
use Alshahari\AuthTracker\Parsers\WhichBrowser;
use Alshahari\AuthTracker\Services\DeviceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;

class DeviceFactory
{
    /**
     * Build a new .
     *
     * @param string
     * @return 
     * @throws 
     */
    public static function build($isRequired = false)
    {

        $request =request();
     //$deviceUdid = $request->header('X-Device-UDID');
       $deviceUdid = $request->header('x-device-udid');
//       Log::info('DeviceFactory device id=>'.$deviceUdid);
//        if(empty($deviceUdid) || $deviceUdid == null){
//            $deviceUdid = $request->header('X-Device-UDID');
//        }

        if (empty($deviceUdid) && ! $isRequired) {
            // We continue on
            $rep = new DeviceService();
            $rep->setHeader();
//            $rep->saveDevice();
            $deviceUdid=$rep->getDeviceId();
            $device= $rep->saveDevice();
            return $device;
        }

        if (empty($deviceUdid) && $isRequired) {
            throw new UnauthorizedException('You need to specify your device details.');
        }

       // dd($deviceUdid);
        // We save the device details
        $device = app(config('auth_tracker.device_model'))->query()->where([
            'udid' => $deviceUdid,
        ])->first();

        if($device == null){
            $rep = new DeviceService();
            $rep->setHeader();
            $device= $rep->saveDevice();
            $deviceUdid=$rep->getDeviceId();

        //    $device=$rep->hasDeviceId($deviceUdid);
        }

//        $request->device = $device;
//
//        $request->guard = $guard ?? config('auth.defaults.guard');
        
        return  $device;
    }
}
