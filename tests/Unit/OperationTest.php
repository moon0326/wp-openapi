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
        $operation = new Operation('get', [$response], '/');
        $this->assertInstanceOf(Operation::class, $operation);
    }

    public function testShouldFilterRequestBody()
    {
        add_filter('wp_openapi_operation_request_body', function($requestBody, $operation) {
            $this->assertInstanceOf(Operation::class, $operation);
            $schema = $requestBody['content']['application/x-www-form-urlencoded']['schema'];
            return [
                'content' => [
                    'application/json' => [
                        'schema' => $schema
                    ]
                ]
            ];
        }, 10, 2);
        $response = new Response(200, 'OK');
        $operation = new Operation('post', [$response], '/');
        $operation->generateParametersFromRouteArgs('post', ['name' => ['type' => 'string', 'required' => true]], []);
        $arr = $operation->toArray();
        $this->assertArrayHasKey('application/json', $arr['requestBody']['content']);
    }
}
