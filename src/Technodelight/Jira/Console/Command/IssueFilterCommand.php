<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Api\Api;
use Technodelight\Jira\Template\IssueRenderer;

class IssueFilterCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $jql;

    /**
     * Constructor.
     *
     * @param string $name The name of the command; passing null means it must be set in configure()
     * @param string $jql JQL Filter to use
     *
     * @throws \LogicException When the command name is empty
     */
    public function __construct(ContainerBuilder $container, $name, $jql)
    {
        if (empty($jql)) {
            throw new \InvalidArgumentException('JQL is empty');
        }
        $this->jql = $jql;

        parent::__construct($container, $name);
    }

    protected function configure()
    {
        $this
            ->setDescription($this->descriptionFromJql())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        /** @var IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->setOutput($output);
        $renderer->renderIssues($jira->search($this->jql, Api::FIELDS_ALL));
    }

    private function descriptionFromJql()
    {
        return sprintf(
            'Runs filter: \'%s\'', $this->jql
        );
    }
}
