<?php

/**
 * germania-kg/fabricsapi-client
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Germania\FabricsApiClient;

use Germania\Cache\CacheCallable;
use Germania\Fabrics\FabricInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CacheFabricsApiClient implements FabricsApiClientInterface
{
    use LoggerAwareTrait;

    /**
     * @var FabricsApiClientInterface
     */
    public $api_client;

    /**
     * @var string
     */
    public $cache_key_separator = '|';

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache_itempool;

    /**
     * @var int
     */
    protected $cache_lifetime;

    /**
     * @var callable
     */
    protected $cache_callable;

    /**
     * @param CacheItemPoolInterface $cache_itempool PSR-6 Cache Item Pool
     * @param int                    $cache_lifetime Cache lifetime in seconds
     * @param null|LoggerInterface   $logger         Optional: PSR-3 Logger
     */
    public function __construct(FabricsApiClientInterface $api_client, CacheItemPoolInterface $cache_itempool, int $cache_lifetime, LoggerInterface $logger = null)
    {
        $this->api_client = $api_client;
        $this->cache_itempool = $cache_itempool;
        $this->cache_lifetime = $cache_lifetime;

        $this->setLogger($logger ?: new NullLogger());
        $this->cache_callable = new CacheCallable($this->cache_itempool, $this->cache_lifetime, function () { return null; }, $this->logger);
    }

    /**
     * {@inheritDoc}
     */
    public function collection(string $collection, string $search = null, $sort = null): iterable
    {
        $cache_key_data = [
            'search',
            $collection,
        ];

        if ($search) {
            $cache_key_data[] = "{$search}";
        }

        if ($sort) {
            $cache_key_data[] = "sort={$sort}";
        }

        $cache_key = implode($this->cache_key_separator, $cache_key_data);

        $cacheResult = ($this->cache_callable)($cache_key, function () use ($collection, $search, $sort) {
            try {
                return $this->api_client->collection($collection, $search, $sort);
            } catch (\Throwable $e) {
                return ExceptionSimplifiedExcerpt::fromThrowable($e);
            }
        });

        if ($cacheResult instanceof ExceptionSimplifiedExcerpt) {
            throw $cacheResult->restoreThrowable();
        }

        return $cacheResult;
    }

    /**
     * {@inheritDoc}
     *
     * @return \ArrayIterator
     */
    public function collections(): iterable
    {
        $cache_key = $this->createCacheKey('list', 'collections');
        $cacheResult = ($this->cache_callable)($cache_key, function () {
            try {
                return $this->api_client->collections();
            } catch (\Throwable $e) {
                return ExceptionSimplifiedExcerpt::fromThrowable($e);
            }
        });

        if ($cacheResult instanceof ExceptionSimplifiedExcerpt) {
            throw $cacheResult->restoreThrowable();
        }

        return $cacheResult;
    }

    /**
     * {@inheritDoc}
     */
    public function collectionTransparencies(string $collection): iterable
    {
        $cache_key = $this->createCacheKey('search', $collection, 'transparencies');

        $cacheResult = ($this->cache_callable)($cache_key, function () use ($collection) {
            try {
                return $this->api_client->collectionTransparencies($collection);
            } catch (\Throwable $e) {
                return ExceptionSimplifiedExcerpt::fromThrowable($e);
            }
        });

        if ($cacheResult instanceof ExceptionSimplifiedExcerpt) {
            throw $cacheResult->restoreThrowable();
        }

        return $cacheResult;
    }

    /**
     * {@inheritDoc}
     */
    public function collectionColors(string $collection): iterable
    {
        $cache_key = $this->createCacheKey('search', $collection, 'colors');

        $cacheResult = ($this->cache_callable)($cache_key, function () use ($collection) {
            try {
                return $this->api_client->collectionColors($collection);
            } catch (\Throwable $e) {
                return ExceptionSimplifiedExcerpt::fromThrowable($e);
            }
        });

        if ($cacheResult instanceof ExceptionSimplifiedExcerpt) {
            throw $cacheResult->restoreThrowable();
        }

        return $cacheResult;
    }

    /**
     * {@inheritDoc}
     */
    public function fabric(string $collection, string $fabric): FabricInterface
    {
        $cache_key = $this->createCacheKey('collections', $collection, 'fabrics', $fabric);

        $cacheResult = ($this->cache_callable)($cache_key, function () use ($collection, $fabric) {
            try {
                return $this->api_client->fabric($collection, $fabric);
            } catch (\Throwable $e) {
                return ExceptionSimplifiedExcerpt::fromThrowable($e);
            }
        });

        if ($cacheResult instanceof ExceptionSimplifiedExcerpt) {
            throw $cacheResult->restoreThrowable();
        }

        return $cacheResult;
    }

    protected function createCacheKey(...$params)
    {
        return implode($this->cache_key_separator, $params);
    }
}
