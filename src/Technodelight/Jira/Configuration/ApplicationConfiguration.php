<?php

namespace Technodelight\Jira\Configuration;

class ApplicationConfiguration
{
    private $username;
    private $password;
    private $githubToken;
    private $domain;
    private $aliases;
    private $yesterdayAsWeekday;
    private $defaultWorklogTimestamp;
    private $cacheTtl;
    private $transitions;
    private $filters;

    public function username()
    {
        return $this->username;
    }

    public function password()
    {
        return $this->password;
    }

    public function githubToken()
    {
        return $this->githubToken;
    }

    public function domain()
    {
        return $this->domain;
    }

    public function yesterdayAsWeekday()
    {
        return $this->yesterdayAsWeekday;
    }

    public function defaultWorklogTimestamp()
    {
        return $this->defaultWorklogTimestamp;
    }

    public function cacheTtl()
    {
        return $this->cacheTtl;
    }

    public function transitions()
    {
        return $this->transitions;
    }

    public function aliases()
    {
        return $this->aliases;
    }

    public function filters()
    {
        return $this->filters;
    }

    public static function fromSymfonyConfigArray(array $config)
    {
        $configuration = new self;
        $configuration->username = $config['credentials']['username'];
        $configuration->password = $config['credentials']['password'];
        $configuration->domain = $config['credentials']['domain'];
        $configuration->githubToken = $config['integrations']['github']['apiToken'];
        $configuration->yesterdayAsWeekday = $config['project']['yesterdayAsWeekday'];
        $configuration->defaultWorklogTimestamp = $config['project']['defaultWorklogTimestamp'];
        $configuration->cacheTtl = $config['project']['cacheTtl'];
        $configuration->transitions = self::flattenArray($config['transitions'], 'command', 'transition');
        $configuration->aliases = self::flattenArray($config['aliases'], 'alias','issueKey');
        $configuration->filters = self::flattenArray($config['filters'], 'command', 'jql');

        return $configuration;
    }

    private static function flattenArray(array $array, $key, $valueKey)
    {
        $result = [];
        foreach ($array as $arr) {
            $result[$arr[$key]] = $arr[$valueKey];
        }
        return $result;
    }
}
