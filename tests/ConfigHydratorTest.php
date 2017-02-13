<?php declare(strict_types=1);

namespace Kassko\Util\ConfigTest;

use Kassko\Util\Config\ConfigHydrator;
use Kassko\Util\MemberAccessor\ObjectMemberAccessor;
use Kassko\Util\Reflection\DocCommentParser;
use Kassko\Util\Reflection\ReflectorRepository;

class ConfigHydratorTest extends \PHPUnit_Framework_TestCase
{
    private $configHydrator;
    private $memberAccessor;

    public function setUp()
    {
        $this->configHydrator = new ConfigHydrator(
            new ReflectorRepository
        );

        $this->memberAccessor = new ObjectMemberAccessor;
    }

    /** @tests */
    public function hydrateCollection()
    {
        $config = [
            'foo' => [
                'firstname' => 'foo',
                'address' => [
                    'state' => 'Philadelphia',
                    'country' => 'United State',
                ],
                'phones' => [
                    '06 02 03 04 05',
                    '01 02 03 04 05',
                ]
            ],
            'bar' => [
                'firstname' => 'bar',
                'address' => [
                    'state' => 'California',
                    'country' => 'United State',
                ],
                'phones' => [
                    '06 02 03 04 05',
                    '01 02 03 04 05',
                ],
                'fallback_addresses' => [
                    'home_address' => [
                        'state' => 'California',
                        'country' => 'United State',
                    ],
                    'office_address' => [
                        'state' => 'San Atonio',
                        'country' => 'United State',
                    ],
                ]
            ]
        ];

        $result = $this->configHydrator->hydrateCollection($config, 'Kassko\Util\ConfigTest\Fixtures\Person');
        print_r($result);

        /*$tags = $this->memberAccessor->executeMethod($this->docCommentParser, 'getTags');

        $this->assertCount(3, $tags);

        $this->assertInstanceOf('Kassko\Util\Reflection\Tag\Param', $tags[0]);
        $this->assertEquals('param', $tags[0]->getName());
        $this->assertEquals('string', $tags[0]->getPropType());
        $this->assertEquals('name', $tags[0]->getPropName());
        $this->assertEquals('My name', $tags[0]->getPropDescription());

        $this->assertInstanceOf('Kassko\Util\Reflection\Tag\Param', $tags[1]);
        $this->assertEquals('param', $tags[1]->getName());
        $this->assertEquals('int', $tags[1]->getPropType());
        $this->assertEquals('age', $tags[1]->getPropName());
        $this->assertNull($tags[1]->getPropDescription());

        $this->assertInstanceOf('Kassko\Util\Reflection\Tag\Throws', $tags[2]);
        $this->assertEquals('throws', $tags[2]->getName());
        $this->assertEquals('\Exception', $tags[2]->getPropType());
        $this->assertEquals('My exception', $tags[2]->getPropDescription());


        $tags = $this->memberAccessor->executeMethod($this->docCommentParser, 'getCustomTags');

        $this->assertCount(1, $tags);
        $this->assertInstanceOf('Kassko\Util\Reflection\Tag', $tags[0]);
        $this->assertEquals('mytag', $tags[0]->getName());
        $this->assertEquals('My description', $tags[0]->getField(0));*/
    }
}
