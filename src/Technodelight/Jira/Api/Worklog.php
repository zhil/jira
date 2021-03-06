<?php

namespace Technodelight\Jira\Api;

use Technodelight\Jira\Helper\DateHelper;

class Worklog
{
    private $issueKey, $worklogId, $author, $comment, $date, $timeSpent, $timeSpentSeconds, $issue = null;

    private function __construct($issueKey, $worklogId, $author, $comment, $date, $timeSpent, $timeSpentSeconds)
    {
        $this->issueKey = $issueKey;
        $this->worklogId = $worklogId;
        $this->author = User::fromArray($author);
        $this->comment = $comment;
        $this->date = $date;
        $this->timeSpent = $timeSpent;
        $this->timeSpentSeconds = $timeSpentSeconds;
    }

    /**
     * @param array $record
     * @param string $issueKey
     * @return Worklog
     */
    public static function fromArray(array $record, $issueKey)
    {
        return new self(
            $issueKey,
            $record['id'],
            $record['author'],
            isset($record['comment']) ? $record['comment'] : null,
            DateHelper::dateTimeFromJira($record['started'])->format('Y-m-d H:i:s'),
            $record['timeSpent'],
            $record['timeSpentSeconds']
        );
    }

    public static function fromIssueAndArray(Issue $issue, array $record)
    {
        $worklog = self::fromArray($record, $issue->key());
        $worklog->issue = $issue;
        return $worklog;
    }

    public function issueKey()
    {
        return $this->issueKey;
    }

    public function issue()
    {
        return $this->issue;
    }

    public function id()
    {
        return $this->worklogId;
    }

    public function author()
    {
        return $this->author;
    }

    public function comment($comment = null)
    {
        if ($comment) {
            $this->comment = $comment;
            return $this;
        }
        return $this->comment;
    }

    public function date($date = null)
    {
        if ($date) {
            $this->date = $date;
            return $this;
        }
        return $this->date;
    }

    public function timeSpent($timeSpent = null)
    {
        if ($timeSpent) {
            $this->timeSpent = $timeSpent;
            $this->timeSpentSeconds = null;
            return $this;
        }
        return $this->timeSpent;
    }

    public function timeSpentSeconds()
    {
        return $this->timeSpentSeconds;
    }

    public function isSame(Worklog $log)
    {
        return [$log->timeSpent, $log->comment, $log->date, $log->author()]
            == [$this->timeSpent, $this->comment, $this->date, $this->author()];
    }
}
