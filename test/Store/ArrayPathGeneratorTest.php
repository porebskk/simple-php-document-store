<?php

namespace SimplePhpDocumentStore\Store;

use PHPUnit\Framework\TestCase;

class ArrayPathGeneratorTest extends TestCase
{
    /** @var ArrayPathGenerator */
    private $generator;

    public function setUp()
    {
        parent::setUp();

        $this->generator = new ArrayPathGenerator();
    }

    public function testGenerateNumberIndexArrays()
    {
        $array = [
            'items' => [
                'shoe',
                'scarf',
                'glass' => [
                    'stylish',
                    'oldschool',
                ],
            ],
        ];

        $generatedData = $this->generator->generate($array);

        $expectedData = [
            ['items' => 'shoe'],
            ['items' => 'scarf'],
            ['items.glass' => 'stylish'],
            ['items.glass' => 'oldschool'],
        ];
        reset($expectedData);
        foreach ($generatedData as $path => $value) {
            $expectedPair = current($expectedData);
            reset($expectedPair);
            $expectedKey = key($expectedPair);
            $expectedValue = current($expectedPair);

            $this->assertSame($expectedKey, $path);
            $this->assertSame($expectedValue, $value);
            next($expectedData);
        }
    }

    public function testGenerateKeyValuePairs()
    {
        $array = [
            'characters' => [
                'MickeyMouse' => [
                    'hair' => 'black',
                    'size' => 'small',
                ],
                'PhilipFry'   => [
                    'hair' => 'orange',
                    'size' => 'normal',
                ],
            ],
            'john'       => 'son',
        ];


        $generatedData = $this->generator->generate($array);

        $expectedData = [
            'characters.MickeyMouse.hair' => 'black',
            'characters.MickeyMouse.size' => 'small',
            'characters.PhilipFry.hair'   => 'orange',
            'characters.PhilipFry.size'   => 'normal',
            'john'                        => 'son',
        ];
        reset($expectedData);
        foreach ($generatedData as $path => $value) {
            $expectedKey = key($expectedData);
            $expectedValue = current($expectedData);

            $this->assertSame($expectedKey, $path);
            $this->assertSame($expectedValue, $value);
            next($expectedData);
        }
    }
}
