<?php
namespace Recharge\Recharge\Base;

use Recharge\Recharge\Application;
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