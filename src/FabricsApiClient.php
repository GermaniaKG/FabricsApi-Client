<?php

/**
 * germania-kg/fabricsapi-client
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Germania\FabricsApiClient;

use Germania\Fabrics\FabricFactory;
use Germania\Fabrics\FabricInterface;
use Germania\Fabrics\FabricNotFoundException;
use Germania\ResponseDecoder\JsonApiResponseDecoder;
use Germania\ResponseDecoder\ResponseDecoderTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class FabricsApiClient implements FabricsApiClientInterface
{
    use LoggerAwareTrait;
    use ResponseDecoderTrait;

    /**
     * @var \GuzzleHttp\Client
     */
    public $client;

    public $fabric_factory;

    /**
     * @var string
     */
    protected $error_loglevel = LogLevel::ERROR;

    /**
     * @param Client               $client Guzzle Client
     * @param null|LoggerInterface $logger Optional: PSR-3 Logger
     */
    public function __construct(Client $client, LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->fabric_factory = new FabricFactory();

        $this->setLogger($logger ?: new NullLogger());

        $this->setResponseDecoder(new JsonApiResponseDecoder());
    }

    /**
     * {@inheritDoc}
     *
     * @return \ArrayIterator
     */
    public function collections(): iterable
    {
        $url = 'collections';
        $fabrics = $this->askApi($url);

        return new \ArrayIterator($fabrics);
    }

    /**
     * {@inheritDoc}
     *
     * @return \ArrayIterator
     */
    public function collection(string $collection, string $search = null, $sort = null): iterable
    {
        $url = sprintf('collections/%s', $collection);
        $query_params = [];

        if ($search) {
            $query_params['search'] = $search;
        }

        if (!is_null($sort)) {
            if (is_array($sort)) {
                $sort = implode(',', $sort);
            } elseif (!is_string($sort)) {
                throw new \UnexpectedValueException('Expected array or CSV string');
            }

            if (!empty($sort)) {
                $query_params['sort'] = $sort;
            }
        }
        $fabrics = $this->askApi($url, $query_params);
        $fabrics = array_map($this->fabric_factory, $fabrics);

        return new \ArrayIterator($fabrics);
    }

    /**
     * {@inheritDoc}
     *
     * @return \ArrayIterator
     */
    public function collectionTransparencies(string $collection): iterable
    {
        $url = sprintf('collections/%s/transparencies', $collection);

        $transparencies = $this->askApi($url);

        return new \ArrayIterator($transparencies);
    }

    /**
     * {@inheritDoc}
     *
     * @return \ArrayIterator
     */
    public function collectionColors(string $collection): iterable
    {
        $url = sprintf('collections/%s/colors', $collection);

        $colors = $this->askApi($url);

        return new \ArrayIterator($colors);
    }

    /**
     * {@inheritDoc}
     */
    public function fabric(string $collection, string $fabric): FabricInterface
    {
        $url = sprintf('collections/%s/fabrics/%s', $collection, $fabric);

        try {
            $is_collection = false;
            $fabric = $this->askApi($url, [], $is_collection);

            return ($this->fabric_factory)($fabric);
        } catch (RequestException $e) {
            $status_code = $e->getResponse()->getStatusCode();

            switch ($status_code) {
                case 404:
                    throw new FabricNotFoundException($e->getMessage(), 404, $e);

                    break;

                default:
                    throw $e;

                    break;
            }
        }
    }

    /**
     * Performs the HTTP request.
     */
    protected function askApi(string $url, array $query_params = [], bool $is_collection = true): array
    {
        $options = [];
        if (!empty($query_params)) {
            $options['query'] = $query_params;
        }

        try {
            // Will return ResponseInterface!
            $response = $this->client->request('GET', $url, $options);
        } catch (RequestException $e) {
            $sc = $e->getResponse()->getStatusCode();
            $msg = sprintf('FabricsApi request error (%s): %s', $sc, $e->getMessage());
            $this->logger->log($this->error_loglevel, $msg, [
                'exception' => get_class($e),
            ]);

            throw $e;
        }

        try {
            $items = $is_collection
            ? $this->getResponseDecoder()->getResourceCollection($response)
            : $this->getResponseDecoder()->getResource($response);
        } catch (\Throwable $e) {
            $msg = sprintf('FabricsApi decoding error: %s', $e->getMessage());
            $this->logger->log($this->error_loglevel, $msg, [
                'exception' => get_class($e),
                'location' => sprintf('%s:%s', $e->getFile(), $e->getLine()),
            ]);

            throw $e;
        }

        return $items;
    }
}
