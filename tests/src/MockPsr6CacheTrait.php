<?php
namespace tests;

use Prophecy\Argument;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Prophecy\PhpUnit\ProphecyTrait;

trait MockPsr6CacheTrait
{
    use ProphecyTrait;

    /**
     * @param  CacheItemPoolInterface|array $cache_item
     * @param  array  $options              CacheItemPool configuration
     */
    protected function createCacheItemPool( $cache_item, array $options = array() )
    {
        $cache = $this->prophesize( CacheItemPoolInterface::class );

        if ($cache_item instanceOf CacheItemInterface):
            $cache->getItem( Argument::type('string') )->willReturn( $cache_item );

        elseif (is_array( $cache_item )):
            foreach($cache_item as $key => $item)
                $cache->getItem( $cache_item->getKey() )->willReturn( $item );

        else:
            throw new \InvalidArgumentException("CacheItemInterface expected");

        endif;


        if ($options['save'] ?? false):
            $cache->save(  Argument::any() )->shouldBeCalled();
        endif;

        return $cache->reveal();
    }




    protected function createCacheItem( array $options = array() )
    {
        $cache_item = $this->prophesize( CacheItemInterface::class );

        if ($get_value = $options['getKey'] ?? false):
            $cache_item->getKey()->willReturn( $get_value );
        endif;

        if ($get_value = $options['get'] ?? false):
            $cache_item->get()->willReturn( $get_value );
        endif;

        if (isset($options['isHit'])):
            $cache_item->isHit()->willReturn( (bool) $options['isHit'] );
        endif;

        if ($set_value = $options['set'] ?? false):
            if (is_string($set_value)):
                $cache_item->set( $set_value )->shouldBeCalled();
            else:
                $cache_item->set( Argument::any() )->shouldBeCalled();
            endif;
        endif;

        if ($expires_value = $options['expiresAfter'] ?? false):
            if (is_int($expires_value)):
                $cache_item->expiresAfter( $expires_value )->shouldBeCalled();
            else:
                $cache_item->expiresAfter( Argument::any() )->shouldBeCalled();
            endif;
        endif;

        return $cache_item->reveal();
    }



    protected function createMissingCacheItem( array $options = array())
    {
        return $this->createCacheItem( array_merge( $options, [
            'isHit' => false
        ]));
    }


}

