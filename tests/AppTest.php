<?php

namespace Payment\Tests;

use PHPUnit\Framework\TestCase;
use Payment\App;

class AppTest extends TestCase
{
    public function testBuild()
    {
        $w = App::ofpay([])->ofpay([
            'channel' => 'make',
            // ...
        ]);
        var_export($w);
    }
}