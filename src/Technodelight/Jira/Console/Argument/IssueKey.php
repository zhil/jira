<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Api\GitShell\Api as Git;
use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Console\Argument\Exception\MissingIssueKeyException;

class IssueKey
{
    const ISSUE_PATTERN = '~^[A-Z]+-[0-9]+$~';
    const GIT_PATTERN = '~^feature/([A-Z]+-[0-9]+)-(.*)~';

    private $issueKey;

    private function __construct($issueKey)
    {
        // if (!preg_match(self::ISSUE_PATTERN, $issueKey)) {
        if (empty($issueKey)) {
            throw new MissingIssueKeyException;
        }
        $this->issueKey = $issueKey;
    }

    public static function fromString($issueKey)
    {
        return new IssueKey($issueKey);
    }

    public static function fromBranch(Branch $branch)
    {
        $issueKey = '';
        if (preg_match(self::GIT_PATTERN, $branch->name(), $matches)) {
            $issueKey = $matches[1];
        }
        return new IssueKey($issueKey);
    }

    public function __toString()
    {
        return (string) $this->issueKey;
    }
}
