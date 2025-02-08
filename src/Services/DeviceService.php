<?php


namespace abobakerMohsan\AuthTracker\Services;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;


class DeviceService
{

    protected $req;
    protected $agent;
    protected $deviceUdid;



    protected $agetis = [
        'udid' => null,
        'os' => null,
        'os_version' => null,
        'manufacturer' => null,
        'model' => null,
        'fcm_token' => null,
        'app_version' => null,
        'app_type' => null,
        'tenant' => null,
    ];

    public function __construct()
    {
        $this->req = \request();
        $this->agent = new Agent();
        $this->setAgents();
    }

    public function setAgents()
    {

        if (request()->hasHeader('x-device-manufacturer')) {
            $this->agetis['manufacturer'] = request()->header('x-device-manufacturer', '-');
        } else {
            $val = $this->agent->device();
            if ($val == 'WebKit') {
                $this->agetis['manufacturer'] = $this->agent->deviceType();
            } else {
                $this->agetis['manufacturer'] = $val;
            }
        }
        if (request()->hasHeader('x-device-model')) {
            $this->agetis['model'] = request()->header('x-device-model', '-');
        } else {
            if ($this->agent->browser()) {
                $this->agetis['model'] = $this->agent->browser();
            }
        }
        if (request()->hasHeader('x-device-os')) {
            $this->agetis['os'] = request()->header('x-device-os', '-');
            $this->agetis['os_version'] = request()->header('x-device-os-version', '-');
        } else {
            if ($platform = $this->agent->platform()) {
                $this->agetis['os'] = $this->agent->platform();
                $version = $this->agent->version($platform);
                if ($version) {
                    $this->agetis['os_version'] = $version;
                }
            }
        }


        if (request()->hasHeader('x-device-udid')) {
            $this->deviceUdid = request()->header('x-device-udid', $this->generateDeviceUdid());
        } else {
            if ($this->req->cookie('device_uuid')) {
                $dd_old = $this->req->cookie('device_uuid');
                $dd_data = $this->hasDeviceId($dd_old);
                if ($dd_data != null) {
                    $this->deviceUdid = $dd_old;
                } else {
                    $this->deviceUdid = $this->generateDeviceUdid();
                }
            } else {
                $this->deviceUdid = $this->generateDeviceUdid();
            }
        }
        $this->agetis['udid'] = $this->deviceUdid;
        if (request()->hasHeader('x-device-fcm-token')) {
            $this->agetis['fcm_token'] = request()->header('x-device-fcm-token', null);
        } else {
            if ($this->req->cookie('notifyToken')) {
                $this->agetis['fcm_token'] = $this->req->cookie('notifyToken');
            } elseif ($this->req->cookie('fcmToken')) {
                $this->agetis['fcm_token'] = $this->req->cookie('fcmToken');
            }
        }


        if (request()->hasHeader('x-device-app-type')) {
            $this->agetis['app_type'] = request()->header('x-device-app-type', 'other');
        } else {
            $agent = new Agent();
            $login_from='other';
            if ($agent->isDesktop()) {
                $login_from = 'web_pc';
            }
            if ($agent->isMobile()) {
                $login_from = 'web_mobile';
            }
            if ($agent->isTablet()) {
                $login_from = 'web_tablet';
            }
            $this->agetis['app_type'] =$login_from;
        }

        if (request()->hasHeader('x-device-app-version')) {
            $this->agetis['app_version'] = request()->header('x-device-app-version', null);
        } else {
            if ($this->req->cookie('app_version')) {
                $this->agetis['app_version'] = $this->req->cookie('app_version');
            }
        }

        if (tenant()) {
            $this->agetis['tenant'] = tenant()->id;
        } else {
            if (request()->hasHeader('country-code')) {
                $this->agetis['tenant'] = request()->header('country-code', null);
            }
        }


    }


    public function setHeader()
    {


        $manufacturer = request()->header('x-device-manufacturer', $this->agetis['manufacturer']);
        $model = request()->header('x-device-model', $this->agetis['model']);
        $os = request()->header('x-device-os', $this->agetis['os']);
        $osV = request()->header('x-device-os-version', $this->agetis['os_version']);
        $udid = request()->header('x-device-udid', $this->deviceUdid);
        $fcmToken = request()->header('x-device-fcm-token', $this->agetis['fcm_token']);

        $appVersion = request()->header('x-device-app-version', $this->agetis['app_version']);
        $appType = request()->header('x-device-app-type', $this->agetis['app_type']);


        \request()->headers->set('x-device-manufacturer', $manufacturer);
        \request()->headers->set('x-device-model', $model);
        \request()->headers->set('x-device-os', $os);
        \request()->headers->set('x-device-os-version', $osV);
        \request()->headers->set('x-device-udid', $udid);
        // \request()->headers->set('X-Device-UDID',$this->deviceUdid);
        \request()->headers->set('x-device-fcm-token', $fcmToken);

        \request()->headers->set('x-device-app-version', $appVersion);
        \request()->headers->set('x-device-app-type', $appType);
    }


    public function getAgents()
    {

        return $this->agetis;
    }


    public function setAgentOne($key, $val)
    {

        if (array_key_exists($key, $this->agetis)) {
            $this->agetis[$key] = $val;
        }
        return false;
    }

    public function getAgentOne($val)
    {

        if (array_key_exists($val, $this->agetis)) {

            return $this->agetis[$val];
        }
        return false;
    }

    public function generateDeviceUdid()
    {
        $u_id = uniqid('');
        $time = time();
        $string = $this->agent->getUserAgent();
        $v = preg_replace('/[^0-9]/', '', $string);
        $d_id = $time . $v . $u_id;
        $deviceUdid = $d_id;

        return $deviceUdid;
    }

    public function getDeviceId()
    {

        return $this->deviceUdid;
    }

    public function hasDeviceId($dev_id)
    {
        $device = app(config('auth_tracker.device_model'))->query()->where([

            'udid' => $dev_id,
        ])->first();

        return $device;
    }

    public function saveDevice()
    {

        $device = app(config('auth_tracker.device_model'))->query()->updateOrCreate([
            'udid' => $this->deviceUdid,
        ], $this->agetis);

        $device->save();

        return $device;
    }

}






