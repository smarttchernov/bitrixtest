<?php
namespace Bitrix\Scale;

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class Action
 * @package Bitrix\Scale
 */
class Action
{
	protected $id = "";
	protected $userParams = array();
	protected $freeParams = array();
	protected $actionParams = array();
	protected $shellAdapter = null;
	protected $result = array();
	protected $logLevel = Logger::LOG_LEVEL_INFO;

	/**
	 * @param string $actionId
	 * @param array $actionParams
	 * @param string $serverHostname
	 * @param array $userParams
	 * @param array $freeParams
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Exception
	 */
	public function __construct($actionId, $actionParams, $serverHostname="", $userParams = array(), $freeParams = array())
	{
		if(strlen($actionId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("actionId");

		if(!is_array($actionParams) || empty($actionParams))
			throw new \Exception("Params of action ".$actionId." are not defined correctly!");

		if(!isset($actionParams["START_COMMAND_TEMPLATE"]) || strlen($actionParams["START_COMMAND_TEMPLATE"]) <= 0)
			throw new \Exception("Required param START_COMMAND_TEMPLATE of action ".$actionId." are not defined!");

		if(!is_array($userParams))
			throw new \Bitrix\Main\ArgumentTypeException("userParams", "array");

		if(!is_array($freeParams))
			throw new \Bitrix\Main\ArgumentTypeException("freeParams", "array");

		$this->id = $actionId;
		$this->userParams = $userParams;
		$this->freeParams = $freeParams;
		$this->actionParams = $actionParams;
		$this->serverHostname = $serverHostname;
		$this->shellAdapter = new ShellAdapter;

		if(isset($actionParams["LOG_LEVEL"]))
			$this->logLevel = $actionParams["LOG_LEVEL"];
	}

	protected function getServerParams()
	{
		return  ServersData::getServer($this->serverHostname);
	}

	/**
	 * Makes command for shell action execution
	 * @param array $inputParams
	 * @return string - command to execute
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	protected function makeStartCommand($inputParams = array())
	{
		if(!is_array($inputParams))
			throw new \Bitrix\Main\ArgumentTypeException("inputParams", "array");

		$retStr = $this->actionParams["START_COMMAND_TEMPLATE"];

		foreach ($this->userParams as $key => $paramValue)
			$retStr = str_replace('##USER_PARAMS:'.$key.'##', $paramValue, $retStr);

		if(strlen($this->serverHostname) > 0 && $this->serverHostname != "global")
		{
			$serverParams = $this->getServerParams();
			$serverParams["hostname"] = $this->serverHostname;

			if(is_array($serverParams))
				foreach ($serverParams as $key => $paramValue)
					$retStr = str_replace('##SERVER_PARAMS:'.$key.'##', $paramValue, $retStr);
		}

		if(!empty($inputParams))
			foreach ($inputParams as $key => $paramValue)
				$retStr = str_replace('##INPUT_PARAMS:'.$key.'##', $paramValue, $retStr);

		if(isset($this->actionParams["CODE_PARAMS"]) && is_array($this->actionParams["CODE_PARAMS"]))
		{
			foreach($this->actionParams["CODE_PARAMS"] as $paramId => $paramCode)
			{
				$func = create_function("", $paramCode);

				if(is_callable($func))
				{
					$res = $func();
					$retStr = str_replace('##CODE_PARAMS:'.$paramId.'##', $res, $retStr);
				}
			}
		}

		foreach ($this->freeParams as $key => $paramValue)
			$retStr = str_replace('##'.$key.'##', $paramValue, $retStr);

		return $retStr;
	}

	/**
	 * Starts the action execution
	 * @param array $inputParams
	 * @return int code returned by shell
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function start($inputParams = array())
	{
		if(!is_array($inputParams))
			throw new \Bitrix\Main\ArgumentTypeException("inputParams", "array");

		if(isset($this->actionParams["CHECK_EXTRA_DB"])	&& $this->actionParams["CHECK_EXTRA_DB"] == "Y"	)
		{
			$dbList = ServersData::getDbList($this->serverHostname);
			$connection = \Bitrix\Main\Application::getConnection();
			$currentDb = $connection->getDbName();
			$dbCount = count($dbList);
			if($dbCount > 1
				||($dbCount == 1
					&& !in_array($currentDb, $dbList)
				)
			)
			{
				$error = Loc::getMessage("SCALE_ACTION_EXTRA_DB_EXIST");
				$this->makeLogRecords("", false, "", $error);
				throw new \Exception(Loc::getMessage("SCALE_ACTION_EXTRA_DB_EXIST"));
			}
		}

		$result = null;
		$command = $this->makeStartCommand($inputParams);
		$result =  $this->shellAdapter->syncExec($command);

		$output = $this->shellAdapter->getLastOutput();

		$arOutput = array();

		if(strlen($output) > 0)
		{
			$arOut = json_decode($output, true);

			if(is_array($arOut) && !empty($arOut))
				$arOutput = $arOut;
		}

		$error = $this->shellAdapter->getLastError();

		$this->makeLogRecords($command, $result, $output, $error);

		$this->result = array(
			$this->id => array(
				"NAME" => isset($this->actionParams["NAME"]) ? $this->actionParams["NAME"] : "[".$this->id."]",
				"RESULT" => $result ? "OK" : "ERROR",
				"OUTPUT" => array(
					"TEXT" => $output,
					"DATA" => $arOutput
				),
				"ERROR" => $error
			)
		);

		return $result;
	}

	/**
	 * @return array Last command execution results
	 */
	public function getResult()
	{
		return  $this->result;
	}

	protected function makeLogRecords($command = "", $result = null, $output = "", $error = "")
	{
		if(strlen($command) > 0)
		{
			//cut password data from log records
			$preg = "/(-p.*\s+|--mysql_password=.*\s+|--cluster_password=.*\s+|--replica_password=.*\s+|--password=.*\s+)/is";
			$command = preg_replace($preg, ' PASS_PARAMS ', $command);

			$this->log(
				($result ? Logger::LOG_LEVEL_INFO : Logger::LOG_LEVEL_ERROR),
				"SCALE_ACTION_STARTED",
				$this->actionParams["NAME"],
				$command
			);
		}

		if($result !== null)
		{
			$this->log(
				($result ? Logger::LOG_LEVEL_INFO : Logger::LOG_LEVEL_ERROR),
				"SCALE_ACTION_RESULT",
				$this->actionParams["NAME"],
				$result ? Loc::getMessage("SCALE_ACTION_RESULT_SUCCESS") : Loc::getMessage("SCALE_ACTION_RESULT_ERROR")
			);
		}

		if(strlen(output) > 0)
		{
			$this->log(
				Logger::LOG_LEVEL_DEBUG,
				"SCALE_ACTION_OUTPUT",
				$this->actionParams["NAME"],
				$output
			);
		}

		if(strlen($error) > 0)
		{
			$this->log(
				Logger::LOG_LEVEL_ERROR,
				"SCALE_ACTION_ERROR",
				$this->actionParams["NAME"],
				$error
			);
		}
	}

	protected function log($level, $auditType, $actionId, $description)
	{
		if($this->logLevel < $level)
			return false;

		return Logger::addRecord($level, $auditType, $actionId, $description);
	}
}