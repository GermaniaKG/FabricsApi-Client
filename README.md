<img src="https://static.germania-kg.com/logos/ga-logo-2016-web.svgz" width="250px">

------




# Germania KG · FabricsAPI client



## Installation

This is a private package, so your project's **composer.json** must know the repo:

```json
"require": {
	"germania/fabricsapi-client": "^3.0"
},

"repositories": [
    {
        "type": "vcs",
        "url":  "git@bitbucket.org:germania/fabricsapi-client.git"
    }
]
```



## Instantiation

The **FabricsApiClient** requures a **Guzzle client** with configured API base URL at hand:

```php
<?php
use Germania\FabricsApiClient\FabricsApiClient;

$guzzle = new \GuzzleHttp\Client([
  // Note the trailing slash!
  'base_uri' => "https://path/to/api/"
]);

$reader = new FabricsApiClient($guzzle);
```

### Cache results

For better performance, a **PSR-6 CacheItemPool** may be used to cache the results. The **CacheFabricsApiClient** wraps the above *FabricsApiClient:* 

```php
<?php
use Germania\FabricsApiClient\CacheFabricsApiClient;

$api_reader = new FabricsApiClient($guzzle);
$psr6 = ...;
$lifetime = 86400;

$reader = new CacheFabricsApiClient($api_reader, $psr6, $lifetime);
```



## Usage

### Read all fabrics

```php
// iterable
$all_fabrics = $reader->collection("anyCollection");

// Sort by pattern
$all_fabrics = $reader->collection("anyCollection", null, "pattern");
```

### Search fabrics

```php
// iterable
$matching_fabrics = $reader->collection("anyCollection", "seaflower");

// Sort by pattern
$matching_fabrics = $reader->collection("anyCollection", "seaflower", "pattern");
```

### Read single fabric

```php
// Germania\Fabrics\FabricInterface
$single = reader->fabric("anyCollection", "1-2345");
```





## Unit tests

Copy `phpunit.xml.dist` to `phpunit.xml` and adapt to your needs. Obtain Germania's *Fabrics API URL* and the collection *slug*, otherwise the Unit Tests will fail and ask a wrong API…

```xml
<php>
  <env name="FABRICS_API" value="https://api.test.com/" />
  <env name="FABRICS_SLUG" value="duette" />
  <env name="FABRIC_NUMBER" value="1-2345" />
</php>
```



Run [PhpUnit](https://phpunit.de/) test or composer scripts like this:

```bash
$ composer test
# or
$ vendor/bin/phpunit
```

