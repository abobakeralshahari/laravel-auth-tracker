<?php

namespace OwaisKit\AuthTracker\IpProviders;

use OwaisKit\AuthTracker\Interfaces\IpProvider;
use OwaisKit\AuthTracker\Traits\MakesApiCalls;
use GuzzleHttp\Psr7\Request;

class IpApi implements IpProvider
{
    use MakesApiCalls;

    /**
     * Get the Guzzle request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return new Request('GET', 'http://ip-api.com/json/'.request()->ip().'?fields=25');
    }

    /**
     * Get the country name.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->result->get('country');
    }

    /**
     * Get the region name.
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->result->get('regionName');
    }

    /**
     * Get the city name.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->result->get('city');
    }
}
