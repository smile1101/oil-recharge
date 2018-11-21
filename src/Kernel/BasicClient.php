<?php
namespace Recharge\Kernel;

use Recharge\Traits\HttpRequestTraits;

class BasicClient
{

    use HttpRequestTraits;

    protected $app;

    /**
     * BasicClient constructor.
     * @param ServiceContainer $app
     */
    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
    }
}