<?php

namespace Fuzz\ApiServer\Notifier\Handlers\Jira;

use Carbon\Carbon;
use Fuzz\ApiServer\Notifier\BaseNotifier;
use Fuzz\ApiServer\Notifier\Contracts\Notifier;
use Fuzz\ApiServer\Notifier\Traits\ReadsRequestId;
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\JqlQuery;
use JiraRestApi\Issue\Version;
use JiraRestApi\JiraException;
use JsonMapper_Exception;

class JiraHandler extends BaseNotifier implements Notifier
{
	use ReadsRequestId;

	/**
	 * @var string
	 */
	private $app_name;

	/**
	 * @var array
	 */
	private $components;

	/**
	 * @var string
	 */
	private $environment;

	/**
	 * @var string
	 */
	private $context;

	/**
	 * @var string
	 */
	private $error_class;

	/**
	 * @var string
	 */
	private $error_file;

	/**
	 * @var int
	 */
	private $line_number;

	/**
	 * Issue service client
	 *
	 * @var \JiraRestApi\Issue\IssueService
	 */
	private $issues;

	/**
	 * JiraHandler constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		parent::__construct($config);

		$this->app_name   = config('app.name');
		$this->components = $config['components'];
	}

	/**
	 * Notify with an error
	 *
	 * @param \Throwable $e
	 * @param string     $environment
	 * @param string     $severity
	 * @param array      $meta
	 *
	 * @return bool
	 */
	public function notifyError(\Throwable $e, string $environment, string $severity = self::URGENT, array $meta = []): bool
	{
		// Don't send these emails for ignored environments
		if (in_array($environment, $this->config['ignore_environments'])) {
			return false;
		}

		// Prevent infinite loops of errors
		if ($e instanceof JiraException || $e instanceof JsonMapper_Exception) {
			return false;
		}

		$this->environment = $environment;
		$this->error_class = class_basename(get_class($e));
		$this->error_file  = basename($e->getFile());
		$this->line_number = $e->getLine();
		$this->issues      = $this->issueClient();
		$name              = $this->getNameForIssue();

		if (! $this->shouldCreateIssue($name)) {
			return false;
		}

		$trace         = $e->getTraceAsString();
		$now_utc       = Carbon::now();
		$now_est       = Carbon::now($this->config['local_tz']);
		$meta          = print_r($meta, true);
		$message       = $e->getMessage();
		$hostname      = gethostname();
		$os            = php_uname();
		$request_id    = $this->getRequestId();
		$this->context = <<<BODY
Environment: $this->environment
Hostname: $hostname
OS: $os
Severity: $severity
Request/b>: $request_id

Occurred UTC): $now_utc
Occurred EST): $now_est

Meta: $meta

Error: $this->error_class
Message: $message
Stack Trace:
$trace
BODY;

		$this->createIssue($name);

		return true;
	}

	/**
	 * Notify with some info
	 *
	 * @param string $event
	 * @param string $environment
	 * @param array  $meta
	 *
	 * @return bool
	 */
	public function notifyInfo(string $event, string $environment, array $meta = []): bool
	{
		return false;
	}

	/**
	 * Build a standard name for issues
	 *
	 * @return string
	 */
	protected function getNameForIssue()
	{
		$error_name = $this->error_class;
		$file       = $this->error_file;
		$line       = $this->line_number;

		return "[REQ-REVIEW] $error_name in $this->app_name in $file on line $line on $this->environment.";
	}

	/**
	 * Does the issue already exist?
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	protected function shouldCreateIssue(string $name): bool
	{
		$query = new JqlQuery;

		$error_name = $this->error_class;

		$query->setProject($this->config['project_key'])->setType($this->config['errors']['issue_type'])
			  ->addExpression('Summary', JqlQuery::OPERATOR_CONTAINS, $error_name)
			  ->addExpression('Summary', JqlQuery::OPERATOR_CONTAINS, $this->environment)
			  ->addExpression('Summary', JqlQuery::OPERATOR_CONTAINS, $this->app_name)
			  ->addInExpression('status', $this->config['search_statuses']);

		try {
			$result = $this->issues->search($query->getQuery());
		} catch (JiraException|JsonMapper_Exception $exception) {
			Log::error($exception->getMessage());

			return true;
		}

		return $result->getTotal() < 1;
	}

	/**
	 * Create a new issue
	 *
	 * @param string $name
	 */
	protected function createIssue(string $name)
	{
		$issue_field = new IssueField;

		$issue_field->setProjectKey($this->config['project_key'])->setSummary($name)
					->setPriorityName($this->config['errors']['priority'])
					->setIssueType($this->config['errors']['issue_type'])->setDescription($this->context)
					->addComponents($this->components);

		foreach ($this->config['errors']['labels'] as $error_label) {
			$issue_field->addLabel($error_label);
		}

		$issue_field->fixVersions = array_map(function (string $version) {
			return new Version($version);
		}, $this->config['versions']);

		try {
			$result = $this->issues->create($issue_field);
		} catch (JiraException|JsonMapper_Exception $exception) {
			Log::error($exception->getMessage(), [
				'issue_name' => $name,
			]);
		}
	}

	/**
	 * Build the issue client
	 *
	 * @return \JiraRestApi\Issue\IssueService
	 */
	protected function issueClient()
	{
		return new IssueService(new ArrayConfiguration($this->config['jira']));
	}
}
