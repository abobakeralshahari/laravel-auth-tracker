<?php

namespace Alshahari\AuthTracker\Exceptions;

use Exception;

class CustomIpProviderException extends Exception
{
    public function __construct()
    {
        parent::__construct('Choose a valid IP address lookup provider. The class must implement the Alshahari\AuthTracker\Interfaces\IpProvider interface.');
    }
}
