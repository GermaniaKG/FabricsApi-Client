<?php
namespace tests;

use Germania\FabricsApiClient\CacheFabricsApiClient;
use Germania\FabricsApiClient\FabricsApiClient;
use Germania\FabricsApiClient\FabricsApiClientInterface;
use Germania\Fabrics\FabricInterface;
use Germania\Fabrics\FabricNotFoundException;

use GuzzleHttp\Client;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class CacheFabricsApiClientTest extends \PHPUnit\Framework\TestCase
{

	use ProphecyTrait,
        MockPsr6CacheTrait;

	public $guzzle;
	public $api_client;
	public $fabrics_slug;
	public $fabric_number;


	public function setUp() : void
	{
		$this->fabrics_slug = getenv('FABRICS_SLUG');
		$this->fabric_number = getenv('FABRIC_NUMBER');

		$this->guzzle = new \GuzzleHttp\Client([
            'base_uri' => getenv('FABRICS_API')
        ]);

		$this->api_client = new FabricsApiClient( $this->guzzle );
	}



	public function testInstantiation()
	{
		$cache_item = $this->createCacheItem();
		$cache_itempool = $this->createCacheItemPool($cache_item);
		$cache_lifetime = 30;

		$sut = new CacheFabricsApiClient( $this->api_client, $cache_itempool, $cache_lifetime );
		$this->assertInstanceOf( FabricsApiClientInterface::class, $sut );

		return $sut;
	}



	public function testSavingCacheItem()
	{
		$cache_item = $this->createCacheItem();
		$cache_itempool = $this->createCacheItemPool($cache_item, [
			'save' => true
		]);
		$cache_lifetime = 30;

		$sut = new CacheFabricsApiClient( $this->api_client, $cache_itempool, $cache_lifetime );
		$fabrics = $sut->collection($this->fabrics_slug);
		$this->assertInstanceOf( \ArrayIterator::class, $fabrics );
	}





	/**
	 * @depends testInstantiation
	 */
	public function testCachedNotFoundException( $sut )
	{
		$this->expectException( FabricNotFoundException::class );
		$sut->fabric($this->fabrics_slug, "DoesNotExist");
	}




	/**
	 * @dataProvider provideVariousCacheResults
	 */
	public function testAllFabricsInCollection( $fabrics_slug, $cache_lifetime)
	{
		$cache_item = $this->createMissingCacheItem([
			'set' => ($cache_lifetime > 0),
			'expiresAfter' => $cache_lifetime
		]);
		$cache_itempool = $this->createCacheItemPool($cache_item,[
			'save' => ($cache_lifetime > 0)
		]);

		$sut = new CacheFabricsApiClient( $this->api_client, $cache_itempool, $cache_lifetime );

		$many_fabrics = $sut->collection($fabrics_slug);
		$this->assertInstanceOf( \ArrayIterator::class, $many_fabrics );
		foreach ($many_fabrics as $f) {
			$this->assertInstanceOf( FabricInterface::class, $f );
		}

	}

	/**
	 * @dataProvider provideVariousCacheResults
	 */
	public function testTransparenciesInCollection( $fabrics_slug, $cache_lifetime)
	{
		$cache_item = $this->createMissingCacheItem([
			'set' => ($cache_lifetime > 0),
			'expiresAfter' => $cache_lifetime
		]);
		$cache_itempool = $this->createCacheItemPool($cache_item,[
			'save' => ($cache_lifetime > 0)
		]);

		$sut = new CacheFabricsApiClient( $this->api_client, $cache_itempool, $cache_lifetime );


		$transparencies = $sut->collectionTransparencies($this->fabrics_slug);
		$this->assertInstanceOf( \ArrayIterator::class, $transparencies );
		foreach ($transparencies as $t) {
			$this->assertIsArray( $t );
		}
	}


	/**
	 * @dataProvider provideVariousCacheResults
	 */
	public function testColorsInCollection( $fabrics_slug, $cache_lifetime)
	{
		$cache_item = $this->createMissingCacheItem([
			'set' => ($cache_lifetime > 0),
			'expiresAfter' => $cache_lifetime
		]);
		$cache_itempool = $this->createCacheItemPool($cache_item,[
			'save' => ($cache_lifetime > 0)
		]);

		$sut = new CacheFabricsApiClient( $this->api_client, $cache_itempool, $cache_lifetime );


		$colors = $sut->collectionColors($this->fabrics_slug);
		$this->assertInstanceOf( \ArrayIterator::class, $colors );
		foreach ($colors as $t) {
			$this->assertIsArray( $t );
		}
	}



	/**
	 * @dataProvider provideVariousCacheResults
	 */
	public function testSingleFabric( $fabrics_slug, $cache_lifetime)
	{
		$cache_item = $this->createMissingCacheItem([
			'set' => ($cache_lifetime > 0),
			'expiresAfter' => $cache_lifetime
		]);
		$cache_itempool = $this->createCacheItemPool($cache_item,[
			'save' => ($cache_lifetime > 0)
		]);

		$sut = new CacheFabricsApiClient( $this->api_client, $cache_itempool, $cache_lifetime );

		$single_fabric = $sut->fabric($fabrics_slug, $this->fabric_number);
		$this->assertInstanceOf( FabricInterface::class, $single_fabric );
	}






	public function provideVariousCacheResults()
	{
		$fabrics_slug = getenv('FABRICS_SLUG');
		return array(
			"$fabrics_slug · lifetime 60" => [ $fabrics_slug, 60 ],
			"$fabrics_slug · lifetime 0" => [ $fabrics_slug, 0 ],
		);
	}


}
