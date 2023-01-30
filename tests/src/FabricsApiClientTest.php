<?php

/**
 * germania-kg/fabricsapi-client
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests;

use Germania\Fabrics\FabricInterface;
use Germania\Fabrics\FabricNotFoundException;
use Germania\FabricsApiClient\FabricsApiClient;
use Germania\FabricsApiClient\FabricsApiClientInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class FabricsApiClientTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public $guzzle;
    public $cache_itempool;
    public $fabrics_slug;
    public $fabric_number;

    /**
     * @var CacheItemPoolInterface
     */
    public $cache_itempool_mock;

    /**
     * @var array
     */
    public static $guzzle_options = [];

    public function setUp(): void
    {
        $this->fabric_number = getenv('FABRIC_NUMBER');
        $this->fabrics_slug = getenv('FABRICS_SLUG');

        $found_certs = glob(realpath(dirname(__DIR__)).'/*.pem');
        $ca_cert = $found_certs[0] ?? null;
        if ($ca_cert) {
            static::$guzzle_options = array_merge(static::$guzzle_options, ['verify' => $ca_cert]);
        }

        $this->guzzle = new \GuzzleHttp\Client(array_merge(static::$guzzle_options, [
            'base_uri' => getenv('FABRICS_API'),
        ]));

        $this->cache_itempool_mock = $this->prophesize(CacheItemPoolInterface::class);
    }

    public function testInstantiation()
    {
        $cip = $this->cache_itempool_mock->reveal();
        $sut = new FabricsApiClient($this->guzzle);

        $this->assertInstanceOf(FabricsApiClientInterface::class, $sut);

        return $sut;
    }

    /**
     * @depends testInstantiation
     *
     * @param mixed $sut
     */
    public function testAllCollections($sut)
    {
        $collections = $sut->collections();
        $this->assertInstanceOf(\ArrayIterator::class, $collections);
        foreach ($collections as $f) {
            $this->assertIsArray($f);
        }
    }

    /**
     * @depends testInstantiation
     *
     * @param mixed $sut
     */
    public function testAllFabricsInCollection($sut)
    {
        $many_fabrics = $sut->collection($this->fabrics_slug);
        $this->assertInstanceOf(\ArrayIterator::class, $many_fabrics);
        foreach ($many_fabrics as $f) {
            $this->assertInstanceOf(FabricInterface::class, $f);
        }
    }

    /**
     * @depends testInstantiation
     *
     * @param mixed $sut
     */
    public function testTransparenciesInCollection($sut)
    {
        $transparencies = $sut->collectionTransparencies($this->fabrics_slug);
        $this->assertInstanceOf(\ArrayIterator::class, $transparencies);
        foreach ($transparencies as $t) {
            $this->assertIsArray($t);
        }
    }

    /**
     * @depends testInstantiation
     *
     * @param mixed $sut
     */
    public function testColorsInCollection($sut)
    {
        $colors = $sut->collectionColors($this->fabrics_slug);
        $this->assertInstanceOf(\ArrayIterator::class, $colors);
        foreach ($colors as $t) {
            $this->assertIsArray($t);
        }
    }

    /**
     * @depends testInstantiation
     *
     * @param mixed $sut
     */
    public function testSingleFabric($sut)
    {
        $single_fabric = $sut->fabric($this->fabrics_slug, $this->fabric_number);
        $this->assertInstanceOf(FabricInterface::class, $single_fabric);
        $this->assertEquals($single_fabric->getFabricNumber(), $this->fabric_number);
        $this->assertEquals($single_fabric->collection_slug, $this->fabrics_slug);
    }

    /**
     * @depends testInstantiation
     *
     * @param mixed $sut
     */
    public function testNotFoundException($sut)
    {
        $this->expectException(FabricNotFoundException::class);
        $sut->fabric($this->fabrics_slug, 'DoesNotExist');
    }
}
