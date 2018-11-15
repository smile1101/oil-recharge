<?php

namespace Recharge\Sf\Base;

use Recharge\Sf\Application;
use Recharge\Traits\HttpRequestTraits;

class BasicClient
{

    use HttpRequestTraits;

    protected $app;

    /**
     * Constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}