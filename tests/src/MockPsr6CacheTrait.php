<?php

/**
 * germania-kg/fabricsapi-client
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

trait MockPsr6CacheTrait
{
    use ProphecyTrait;

    /**
     * @param array|CacheItemPoolInterface $cache_item
     * @param array                        $options    CacheItemPool configuration
     */
    protected function createCacheItemPool($cache_item, array $options = [])
    {
        $cache = $this->prophesize(CacheItemPoolInterface::class);

        if ($cache_item instanceof CacheItemInterface) {
            $cache->getItem(Argument::type('string'))->willReturn($cache_item);
        } elseif (is_array($cache_item)) {
            foreach ($cache_item as $key => $item) {
                $cache->getItem($cache_item->getKey())->willReturn($item);
            }
        } else {
            throw new \InvalidArgumentException('CacheItemInterface expected');
        }

        if ($options['save'] ?? false) {
            $cache->save(Argument::any())->shouldBeCalled();
        }

        return $cache->reveal();
    }

    protected function createCacheItem(array $options = [])
    {
        $cache_item = $this->prophesize(CacheItemInterface::class);

        if ($get_value = $options['getKey'] ?? false) {
            $cache_item->getKey()->willReturn($get_value);
        }

        if ($get_value = $options['get'] ?? false) {
            $cache_item->get()->willReturn($get_value);
        }

        if (isset($options['isHit'])) {
            $cache_item->isHit()->willReturn((bool) $options['isHit']);
        }

        if ($set_value = $options['set'] ?? false) {
            if (is_string($set_value)) {
                $cache_item->set($set_value)->shouldBeCalled();
            } else {
                $cache_item->set(Argument::any())->shouldBeCalled();
            }
        }

        if ($expires_value = $options['expiresAfter'] ?? false) {
            if (is_int($expires_value)) {
                $cache_item->expiresAfter($expires_value)->shouldBeCalled();
            } else {
                $cache_item->expiresAfter(Argument::any())->shouldBeCalled();
            }
        }

        return $cache_item->reveal();
    }

    protected function createMissingCacheItem(array $options = [])
    {
        return $this->createCacheItem(array_merge($options, [
            'isHit' => false,
        ]));
    }
}
