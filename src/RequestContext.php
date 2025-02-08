<?php

namespace abobakerMohsan\AuthTracker;

use abobakerMohsan\AuthTracker\Factories\DeviceFactory;
use abobakerMohsan\AuthTracker\Factories\IpProviderFactory;
use abobakerMohsan\AuthTracker\Factories\ParserFactory;
use abobakerMohsan\AuthTracker\Interfaces\IpProvider;
use abobakerMohsan\AuthTracker\Interfaces\UserAgentParser;
use abobakerMohsan\AuthTracker\Models\Device;
use abobakerMohsan\AuthTracker\Services\DeviceService;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class RequestContext
{
    /**
     * @var UserAgentParser $parser
     */
    protected $parser;

    /**
     * @var Device $device
     */
    public $device;
    
    /**
     * @var IpProvider $ipProvider
     */
    protected $ipProvider = null;

    /**
     * @var string $userAgent
     */
    public $userAgent;

    /**
     * @var string|null $ip
     */
    public $ip;
    
    public $loginBy;
    public $loginFrom;
    public $deviceUdid;

    /**
     * RequestContext constructor.
     *
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
     */
    public function __construct()
    {
        // Initialize the parser
        $this->parser = ParserFactory::build(config('auth_tracker.parser'));
        $this->device = DeviceFactory::build();

//        Log::info('loginFactory_Request device Model =>');
//        Log::info($this->device);

        // Initialize the IP provider
        $this->ipProvider = IpProviderFactory::build(config('auth_tracker.ip_lookup.provider'));

        $this->userAgent = request()->userAgent();
        $this->ip = request()->ip();
        //
        $this->loginBy = request()->has('login_by')?request()->get('login_by'):'other';

        if( request()->hasHeader('x-device-app-type')){

            $this->loginFrom = request()->header('x-device-app-type');
        }else{

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
            $this->loginFrom = request()->has('login_from')?request()->get('login_from'): $login_from;
        }

        if( request()->hasHeader('x-device-udid')){

            $this->deviceUdid = request()->header('x-device-udid');

//            Log::info('loginFactory_Request device id=>'.$this->deviceUdid);
        }else{

            $rep = new DeviceService();
            $rep->setHeader();
            $rep->saveDevice();
            $Udid=$rep->getDeviceId();
            $this->deviceUdid=$Udid;
        }

    }

    /**
     * Get the parser used to parse the User-Agent header.
     *
     * @return UserAgentParser
     */
    public function parser()
    {
        return $this->parser;
    }

    /**
     * Get the parser used to parse the User-Agent header.
     *
     * @return Device
     */
    public function device()
    {
        return $this->device();
    }
    
    
    /**
     * Get the IP lookup result.
     *
     * @return IpProvider
     */
    public function ip()
    {
        if ($this->ipProvider && $this->ipProvider->getResult()) {
            return $this->ipProvider;
        }

        return null;
    }
}
