<?php
namespace tests;

use Germania\FabricsApiClient\FabricsApiClient;
use Germania\FabricsApiClient\FabricsApiClientInterface;
use Germania\Fabrics\FabricInterface;
use Germania\Fabrics\FabricNotFoundException;
use GuzzleHttp\Client;
use Psr\Cache\CacheItemPoolInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class FabricsApiClientTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

	public $guzzle;
	public $cache_itempool;
	public $fabrics_slug;
	public $fabric_number;

	public function setUp() : void
	{
		$this->fabric_number = getenv('FABRIC_NUMBER');
		$this->fabrics_slug = getenv('FABRICS_SLUG');

		$this->guzzle = new \GuzzleHttp\Client([
            'base_uri' => getenv('FABRICS_API')
        ]);

		$this->cache_itempool_mock = $this->prophesize(CacheItemPoolInterface::class);
	}


	public function testInstantiation()
	{
		$cip = $this->cache_itempool_mock->reveal();
		$sut = new FabricsApiClient( $this->guzzle );

		$this->assertInstanceOf( FabricsApiClientInterface::class, $sut );
		return $sut;
	}


	/**
	 * @depends testInstantiation
	 */
	public function testAllFabricsInCollection( $sut )
	{
		$many_fabrics = $sut->collection($this->fabrics_slug);
		$this->assertInstanceOf( \ArrayIterator::class, $many_fabrics );
		foreach ($many_fabrics as $f) {
			$this->assertInstanceOf( FabricInterface::class, $f );
		}
	}

	/**
	 * @depends testInstantiation
	 */
	public function testTransparenciesInCollection($sut)
	{
		$transparencies = $sut->collectionTransparencies($this->fabrics_slug);
		$this->assertInstanceOf( \ArrayIterator::class, $transparencies );
		foreach ($transparencies as $t) {
			$this->assertIsArray( $t );
		}
	}


	/**
	 * @depends testInstantiation
	 */
	public function testColorsInCollection($sut)
	{
		$colors = $sut->collectionColors($this->fabrics_slug);
		$this->assertInstanceOf( \ArrayIterator::class, $colors );
		foreach ($colors as $t) {
			$this->assertIsArray( $t );
		}
	}

	/**
	 * @depends testInstantiation
	 */
	public function testSingleFabric($sut)
	{
		$single_fabric = $sut->fabric($this->fabrics_slug, $this->fabric_number);
		$this->assertInstanceOf( FabricInterface::class, $single_fabric );
        $this->assertEquals( $single_fabric->getFabricNumber(),  $this->fabric_number);
        $this->assertEquals( $single_fabric->collection_slug,  $this->fabrics_slug);
	}






	/**
	 * @depends testInstantiation
	 */
	public function testNotFoundException( $sut )
	{
		$this->expectException( FabricNotFoundException::class );
		$sut->fabric($this->fabrics_slug, "DoesNotExist");
	}


}
