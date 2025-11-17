<?php

namespace WPOpenAPI\Tests\Unit;

use WPOpenAPI\Spec\Parameter;
use WPOpenAPI\Tests\TestCase;

class ParameterTest extends TestCase
{
    public function test_serialization()
    {
        $parameter = new Parameter('query', 'username', 'string', 'username parameter', true);
        $result = $parameter->toArray();
        $expected = [
            'name' => 'username',
            'in' => 'query',
            'description' => 'username parameter',
            'required' => true,
            'schema' => new \stdClass()
        ];
        $this->assertEquals($expected, $result);
    }
}
