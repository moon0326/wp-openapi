<?php

namespace WPOpenAPI\Tests\Unit;

use WPOpenAPI\Spec\Operation;
use WPOpenAPI\Spec\Response;
use WPOpenAPI\Tests\TestCase;

class OperationTest extends TestCase
{
    public function test()
    {
        $response = new Response(200, 'OK');
        $operation = new Operation('get', [$response]);
    }
}
