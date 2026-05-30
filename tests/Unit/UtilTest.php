<?php

namespace WPOpenAPI\Tests\Unit;

use WPOpenAPI\Tests\TestCase;
use WPOpenAPI\Util;

class UtilTest extends TestCase
{
    public function testNormalizeSchemaWithStringDateTimeType()
    {
        $result = Util::normalizeSchema('date-time');
        $this->assertEquals(['type' => 'string', 'format' => 'date-time'], $result);
    }

    public function testNormalizeSchemaWithStringDateType()
    {
        $result = Util::normalizeSchema('date');
        $this->assertEquals(['type' => 'string', 'format' => 'date'], $result);
    }

    public function testNormalizeSchemaWithStringEmailType()
    {
        $result = Util::normalizeSchema('email');
        $this->assertEquals(['type' => 'string', 'format' => 'email'], $result);
    }

    public function testNormalizeSchemaWithStringUriType()
    {
        $result = Util::normalizeSchema('uri');
        $this->assertEquals(['type' => 'string', 'format' => 'uri'], $result);
    }

    public function testNormalizeSchemaWithStringBoolType()
    {
        $result = Util::normalizeSchema('bool');
        $this->assertEquals(['type' => 'boolean'], $result);
    }

    public function testNormalizeSchemaWithStringMixedType()
    {
        $result = Util::normalizeSchema('mixed');
        $this->assertEquals(['type' => 'string'], $result);
    }

    public function testNormalizeSchemaWithStringRegularType()
    {
        $result = Util::normalizeSchema('string');
        $this->assertEquals(['type' => 'string'], $result);
    }

    public function testNormalizeSchemaWithArrayDateTimeType()
    {
        $result = Util::normalizeSchema(['type' => 'date-time']);
        $this->assertEquals(['type' => 'string', 'format' => 'date-time'], $result);
    }

    public function testNormalizeSchemaWithArrayBoolType()
    {
        $result = Util::normalizeSchema(['type' => 'bool']);
        $this->assertEquals(['type' => 'boolean'], $result);
    }

    public function testNormalizeSchemaPreservesExistingFormat()
    {
        $result = Util::normalizeSchema(['type' => 'date-time', 'format' => 'custom-format']);
        $this->assertEquals(['type' => 'date-time', 'format' => 'custom-format'], $result);
    }

    public function testNormalizeSchemaWithArrayOfTypes()
    {
        $result = Util::normalizeSchema(['type' => ['date-time', 'null']]);
        $this->assertEquals(['type' => ['string', 'null'], 'format' => 'date-time'], $result);
    }

    public function testNormalizeSchemaWithArrayOfTypesIncludingBool()
    {
        $result = Util::normalizeSchema(['type' => ['bool', 'null']]);
        $this->assertEquals(['type' => ['boolean', 'null']], $result);
    }

    public function testNormalizeSchemaWithNestedProperties()
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'created_at' => ['type' => 'date-time'],
                'name' => ['type' => 'string'],
                'active' => ['type' => 'bool'],
            ],
        ];

        $result = Util::normalizeSchema($schema);

        $this->assertEquals('string', $result['properties']['created_at']['type']);
        $this->assertEquals('date-time', $result['properties']['created_at']['format']);
        $this->assertEquals('string', $result['properties']['name']['type']);
        $this->assertEquals('boolean', $result['properties']['active']['type']);
    }

    public function testNormalizeSchemaWithItems()
    {
        $schema = [
            'type' => 'array',
            'items' => ['type' => 'date-time'],
        ];

        $result = Util::normalizeSchema($schema);

        $this->assertEquals('string', $result['items']['type']);
        $this->assertEquals('date-time', $result['items']['format']);
    }

    public function testNormalizeSchemaWithOneOf()
    {
        $schema = [
            'oneOf' => [
                ['type' => 'date-time'],
                ['type' => 'bool'],
            ],
        ];

        $result = Util::normalizeSchema($schema);

        $this->assertEquals('string', $result['oneOf'][0]['type']);
        $this->assertEquals('date-time', $result['oneOf'][0]['format']);
        $this->assertEquals('boolean', $result['oneOf'][1]['type']);
    }

    public function testNormalizeSchemaWithAnyOf()
    {
        $schema = [
            'anyOf' => [
                ['type' => 'email'],
                ['type' => 'uri'],
            ],
        ];

        $result = Util::normalizeSchema($schema);

        $this->assertEquals('string', $result['anyOf'][0]['type']);
        $this->assertEquals('email', $result['anyOf'][0]['format']);
        $this->assertEquals('string', $result['anyOf'][1]['type']);
        $this->assertEquals('uri', $result['anyOf'][1]['format']);
    }

    public function testNormalizeSchemaWithAllOf()
    {
        $schema = [
            'allOf' => [
                ['type' => 'date'],
                ['type' => 'mixed'],
            ],
        ];

        $result = Util::normalizeSchema($schema);

        $this->assertEquals('string', $result['allOf'][0]['type']);
        $this->assertEquals('date', $result['allOf'][0]['format']);
        $this->assertEquals('string', $result['allOf'][1]['type']);
    }

    public function testNormalizeSchemaWithAdditionalProperties()
    {
        $schema = [
            'type' => 'object',
            'additionalProperties' => ['type' => 'date-time'],
        ];

        $result = Util::normalizeSchema($schema);

        $this->assertEquals('string', $result['additionalProperties']['type']);
        $this->assertEquals('date-time', $result['additionalProperties']['format']);
    }

    public function testNormalizeSchemaWithDeeplyNestedStructure()
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'timestamp' => ['type' => 'date-time'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = Util::normalizeSchema($schema);

        $this->assertEquals('string', $result['properties']['data']['properties']['items']['items']['properties']['timestamp']['type']);
        $this->assertEquals('date-time', $result['properties']['data']['properties']['items']['items']['properties']['timestamp']['format']);
    }

    public function testNormalizeSchemaPreservesOtherFields()
    {
        $schema = [
            'type' => 'date-time',
            'description' => 'A timestamp',
            'required' => true,
        ];

        $result = Util::normalizeSchema($schema);

        $this->assertEquals('string', $result['type']);
        $this->assertEquals('date-time', $result['format']);
        $this->assertEquals('A timestamp', $result['description']);
        $this->assertTrue($result['required']);
    }
}
