<?php

namespace Technodelight\Jira\Api;

use ICanBoogie\Storage\Storage;
use Technodelight\Jira\Api\Api;
use Technodelight\Jira\Api\HttpClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class CachedHttpClient implements Client
{
    private $httpClient;
    private $storage;
    private $configuration;

    public function __construct(
        HttpClient $httpClient,
        Storage $storage,
        ApplicationConfiguration $configuration
    )
    {
        $this->httpClient = $httpClient;
        $this->storage = $storage;
        $this->configuration = $configuration;
    }

    public function post($url, $data = [])
    {
        if (strpos('worklog/list', $url) === false && strpos('search', $url) === false) {
            $this->storage->clear();
        }
        return $this->httpClient->post($url, $data);
    }

    public function put($url, $data = [])
    {
        $this->storage->clear();
        return $this->httpClient->put($url, $data);
    }

    public function get($url)
    {
        $key = $this->keyify($url);
        $result = $this->storage->retrieve($key);
        if (!is_null($result)) {
            return $result;
        }
        $result = $this->httpClient->get($url);
        $this->storage->store($key, $result, $this->configuration->cacheTtl());
        return $result;
    }

    public function delete($url)
    {
        $this->storage->clear();
        return $this->httpClient->delete($url);
    }

    public function multiGet(array $urls)
    {
        $cachedResults = [];
        $uncachedUrls = [];
        foreach ($urls as $idx => $url) {
            $result = $this->storage->retrieve($this->keyify($url));
            if (!is_null($result)) {
                $cachedResults[$url] = $result;
            } else {
                $uncachedUrls[] = $url;
            }
        }
        $results = [];
        foreach ($this->httpClient->multiGet($uncachedUrls) as $url => $result) {
            $key = $url;
            $this->storage->store($this->keyify($key), $result, $this->configuration->cacheTtl());
            $results[$key] = $result;
        }
        $mergedResults = [];
        foreach ($urls as $url) {
            $mergedResults[$url] = isset($cachedResults[$url]) ? $cachedResults[$url] : $results[$url];
        }
        return $mergedResults;
    }

    public function search($jql, $fields = null, array $expand = null, array $properties = null)
    {
        $key = $this->keyify($jql, (string) $fields, serialize($expand), serialize($properties));
        $result = $this->storage->retrieve($key);
        if (!is_null($result)) {
            return $result;
        }
        $result = $this->httpClient->search($jql, $fields, $expand, $properties);
        $this->storage->store($key, $result, $this->configuration->cacheTtl());
        return $result;
    }

    private function keyify()
    {
        return implode('', func_get_args());
        // return md5(implode('', func_get_args()));
    }
}
