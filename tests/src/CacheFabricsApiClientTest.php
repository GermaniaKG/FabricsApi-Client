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
use Germania\FabricsApiClient\CacheFabricsApiClient;
use Germania\FabricsApiClient\FabricsApiClient;
use Germania\FabricsApiClient\FabricsApiClientInterface;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class CacheFabricsApiClientTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    use MockPsr6CacheTrait;

    public $guzzle;
    public $api_client;
    public $fabrics_slug;
    public $fabric_number;

    /**
     * @var array
     */
    public static $guzzle_options = [];

    public function setUp(): void
    {
        $this->fabrics_slug = getenv('FABRICS_SLUG');
        $this->fabric_number = getenv('FABRIC_NUMBER');

        $found_certs = glob(realpath(dirname(__DIR__)).'/*.pem');
        $ca_cert = $found_certs[0] ?? null;
        if ($ca_cert) {
            static::$guzzle_options = array_merge(static::$guzzle_options, ['verify' => $ca_cert]);
        }

        $this->guzzle = new \GuzzleHttp\Client(array_merge(static::$guzzle_options, [
            'base_uri' => getenv('FABRICS_API'),
        ]));

        $this->api_client = new FabricsApiClient($this->guzzle);
    }

    public function testInstantiation()
    {
        $cache_item = $this->createCacheItem();
        $cache_itempool = $this->createCacheItemPool($cache_item);
        $cache_lifetime = 5;

        $sut = new CacheFabricsApiClient($this->api_client, $cache_itempool, $cache_lifetime);
        $this->assertInstanceOf(FabricsApiClientInterface::class, $sut);

        return $sut;
    }

    public function testSavingCacheItem()
    {
        $cache_item = $this->createCacheItem();
        $cache_itempool = $this->createCacheItemPool($cache_item, [
            'save' => true,
        ]);
        $cache_lifetime = 30;

        $sut = new CacheFabricsApiClient($this->api_client, $cache_itempool, $cache_lifetime);
        $fabrics = $sut->collection($this->fabrics_slug);
        $this->assertInstanceOf(\ArrayIterator::class, $fabrics);
    }

    /**
     * @depends testInstantiation
     *
     * @param mixed $sut
     */
    public function testCachedNotFoundException($sut)
    {
        $this->expectException(FabricNotFoundException::class);
        $sut->fabric($this->fabrics_slug, 'DoesNotExist');
    }

    /**
     * @dataProvider provideVariousCacheResults
     *
     * @param mixed $fabrics_slug
     * @param mixed $cache_lifetime
     */
    public function testAllFabricsInCollection($fabrics_slug, $cache_lifetime)
    {
        $cache_item = $this->createMissingCacheItem([
            'set' => ($cache_lifetime > 0),
            'expiresAfter' => $cache_lifetime,
        ]);
        $cache_itempool = $this->createCacheItemPool($cache_item, [
            'save' => ($cache_lifetime > 0),
        ]);

        $sut = new CacheFabricsApiClient($this->api_client, $cache_itempool, $cache_lifetime);

        $many_fabrics = $sut->collection($fabrics_slug);
        $this->assertInstanceOf(\ArrayIterator::class, $many_fabrics);
        foreach ($many_fabrics as $f) {
            $this->assertInstanceOf(FabricInterface::class, $f);
        }
    }

    /**
     * @dataProvider provideVariousCacheResults
     *
     * @param mixed $fabrics_slug
     * @param mixed $cache_lifetime
     */
    public function testTransparenciesInCollection($fabrics_slug, $cache_lifetime)
    {
        $cache_item = $this->createMissingCacheItem([
            'set' => ($cache_lifetime > 0),
            'expiresAfter' => $cache_lifetime,
        ]);
        $cache_itempool = $this->createCacheItemPool($cache_item, [
            'save' => ($cache_lifetime > 0),
        ]);

        $sut = new CacheFabricsApiClient($this->api_client, $cache_itempool, $cache_lifetime);

        $transparencies = $sut->collectionTransparencies($this->fabrics_slug);
        $this->assertInstanceOf(\ArrayIterator::class, $transparencies);
        foreach ($transparencies as $t) {
            $this->assertIsArray($t);
        }
    }

    /**
     * @dataProvider provideVariousCacheResults
     *
     * @param mixed $fabrics_slug
     * @param mixed $cache_lifetime
     */
    public function testColorsInCollection($fabrics_slug, $cache_lifetime)
    {
        $cache_item = $this->createMissingCacheItem([
            'set' => ($cache_lifetime > 0),
            'expiresAfter' => $cache_lifetime,
        ]);
        $cache_itempool = $this->createCacheItemPool($cache_item, [
            'save' => ($cache_lifetime > 0),
        ]);

        $sut = new CacheFabricsApiClient($this->api_client, $cache_itempool, $cache_lifetime);

        $colors = $sut->collectionColors($this->fabrics_slug);
        $this->assertInstanceOf(\ArrayIterator::class, $colors);
        foreach ($colors as $t) {
            $this->assertIsArray($t);
        }
    }

    /**
     * @dataProvider provideVariousCacheResults
     *
     * @param mixed $fabrics_slug
     * @param mixed $cache_lifetime
     */
    public function testSingleFabric($fabrics_slug, $cache_lifetime)
    {
        $cache_item = $this->createMissingCacheItem([
            'set' => ($cache_lifetime > 0),
            'expiresAfter' => $cache_lifetime,
        ]);
        $cache_itempool = $this->createCacheItemPool($cache_item, [
            'save' => ($cache_lifetime > 0),
        ]);

        $sut = new CacheFabricsApiClient($this->api_client, $cache_itempool, $cache_lifetime);

        $single_fabric = $sut->fabric($fabrics_slug, $this->fabric_number);
        $this->assertInstanceOf(FabricInterface::class, $single_fabric);
    }

    public function provideVariousCacheResults()
    {
        $fabrics_slug = getenv('FABRICS_SLUG');

        return [
            "{$fabrics_slug} · lifetime 60" => [$fabrics_slug, 60],
            "{$fabrics_slug} · lifetime 0" => [$fabrics_slug, 0],
        ];
    }
}
