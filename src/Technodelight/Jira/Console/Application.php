<?php

namespace Technodelight\Jira\Console;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as BaseApp;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Api\Api as JiraApi;
use Technodelight\Jira\Api\Client as JiraClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Command\BrowseIssueCommand;
use Technodelight\Jira\Console\Command\DashboardCommand;
use Technodelight\Jira\Console\Command\InitCommand;
use Technodelight\Jira\Console\Command\IssueFilterCommand;
use Technodelight\Jira\Console\Command\IssueTransitionCommand;
use Technodelight\Jira\Console\Command\ListWorkInProgressCommand;
use Technodelight\Jira\Console\Command\LogTimeCommand;
use Technodelight\Jira\Console\Command\SearchCommand;
use Technodelight\Jira\Console\Command\SelfUpdateCommand;
use Technodelight\Jira\Console\Command\ShowCommand;
use Technodelight\Jira\Console\Command\TodoCommand;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\GitHelper;
use Technodelight\Jira\Helper\HubHelper;
use Technodelight\Jira\Helper\PluralizeHelper;
use Technodelight\Jira\Helper\TemplateHelper;

class Application extends BaseApp
{
    /**
     * Relative path to base source directory
     * @var string
     */
    private $baseDir;

    private $directories = [
        'views' => ['Resources', 'views'],
        'configs' => ['Resources', 'configs']
    ];

    /**
     * DI files to load
     * @var array
     */
    private $diFiles = [
        'helpers.xml',
        'renderers.xml',
        'api.xml',
        'console.xml',
    ];

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var GitHelper
     */
    protected $gitHelper;

    /**
     * @var DateHelper
     */
    protected $dateHelper;

    /**
     * @var GitBranchnameGenerator
     */
    protected $gitBranchnameGenerator;

    /**
     * @var TemplateHelper
     */
    protected $templateHelper;

    /**
     * @var JiraClient
     */
    protected $jira;

    protected $isTesting = false;

    /**
     * Constructor.
     *
     * @param string $name    The name of the application
     * @param string $version The version of the application
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN', $isTesting = false)
    {
        $this->baseDir = $this->path([__DIR__, '..']);
        $this->isTesting = $isTesting;

        parent::__construct($name, $version);
    }

    public function addDomainCommands()
    {
        $commands = [];
        $commands[] = new ListWorkInProgressCommand($this->container());
        $commands[] = new LogTimeCommand($this->container());
        $commands[] = new DashboardCommand($this->container());
        $commands[] = new ShowCommand($this->container());
        $commands[] = new BrowseIssueCommand($this->container());

        foreach ($this->config()->transitions() as $alias => $transitionName) {
            $commands[] = new IssueTransitionCommand($this->container(), $alias, $transitionName);
        }
        $filters = $this->config()->filters();
        foreach ($filters as $alias => $jql) {
            $commands[] = new IssueFilterCommand($this->container(), $alias, $jql);
        }
        $commands[] = new SearchCommand($this->container());

        $this->addCommands($commands);
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new InitCommand($this->container());
        $commands[] = new SelfUpdateCommand($this->container());

        return $commands;
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (true === $input->hasParameterOption(array('--debug', '-d'))) {

            $start = microtime(true);
            $startMem = memory_get_usage(true);
            $result = parent::doRun($input, $output);
            $end = microtime(true) - $start;
            $endMem = memory_get_peak_usage(true);
            $output->writeLn(sprintf('%1.4f s, mem %s', $end, $this->formatBytes($endMem - $startMem)));
            return $result;
        } else {
            return parent::doRun($input, $output);
        }
    }

    /**
     * @return ApplicationConfiguration
     */
    public function config()
    {
        return $this->container()->get('technodelight.jira.config');
    }

    /**
     * @return ContainerBuilder
     */
    public function container()
    {
        if (!isset($this->container)) {
            $this->container = new ContainerBuilder();
            foreach ($this->syntheticContainerServices() as $serviceId => $object) {
                $this->container->set($serviceId, $object);
            }

            $loader = new XmlFileLoader($this->container, new FileLocator($this->directory('configs')));
            foreach ($this->diFiles as $fileName) {
                $loader->load($fileName);
            }
            if ($this->isTesting) {
                $testingLoader = new XmlFileLoader($this->container, new FileLocator([$this->baseDir . '/../../../features/bootstrap/configs']));
                foreach ($this->diFiles as $fileName) {
                    if (is_file($this->baseDir . '/../../../features/bootstrap/configs/' . $fileName)) {
                        $testingLoader->load($fileName);
                    }
                }
            }
        }

        return $this->container;
    }

    /**
     * @return GitHelper
     */
    public function git()
    {
        if (!isset($this->gitHelper)) {
            $this->gitHelper = new GitHelper;
        }

        return $this->gitHelper;
    }

    /**
     * @return GitBranchnameGenerator
     */
    public function gitBranchnameGenerator()
    {
        if (!isset($this->gitBranchnameGenerator)) {
            $this->gitBranchnameGenerator = new GitBranchnameGenerator;
        }

        return $this->gitBranchnameGenerator;
    }

    /**
     * @return TemplateHelper
     */
    public function templateHelper()
    {
        if (!isset($this->templateHelper)) {
            $this->templateHelper = new TemplateHelper;
        }

        return $this->templateHelper;
    }

    /**
     * @return DateHelper
     */
    public function dateHelper()
    {
        if (!isset($this->dateHelper)) {
            $this->dateHelper = new DateHelper;
        }

        return $this->dateHelper;
    }

    /**
     * @return JiraApi
     */
    public function jira()
    {
        if (!isset($this->jira)) {
            $client = new JiraClient($this->config());
            $this->jira = new JiraApi($client);
        }

        return $this->jira;
    }

    public function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new GitHelper);
        $helperSet->set(new PluralizeHelper);
        return $helperSet;
    }

    /**
     * @param  string $alias
     *
     * @return string relative path
     */
    public function directory($alias)
    {
        if (!isset($this->directories[$alias])) {
            throw new \UnexpectedValueException(sprintf('No directory %s configured', $alias));
        }

        return $this->baseDir . DIRECTORY_SEPARATOR . $this->path($this->directories[$alias]);
    }

    protected function getDefaultInputDefinition()
    {
        $input = parent::getDefaultInputDefinition();
        $input->addOption(new InputOption('--debug', '-D', InputOption::VALUE_NONE, 'Enable debug mode'));
        return $input;
    }

    private function path(array $parts)
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function syntheticContainerServices()
    {
        return [
            'technodelight.jira.app' => $this,
            'console.formatter_helper' => $this->getDefaultHelperSet()->get('formatter'),
            'console.dialog_helper' => $this->getDefaultHelperSet()->get('dialog'),
        ];
    }

    private function formatBytes($size, $precision = 4)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }
}
