<?php

namespace Recharge\Oil\Ofpay;

use Recharge\Oil\Base\BasicClient;

class Client extends BasicClient
{
    public function pay($payload)
    {
        return $payload;
    }
}