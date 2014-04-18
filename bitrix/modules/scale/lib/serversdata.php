<?php
namespace Bitrix\Scale;

/**
 * Class ServersData
 * @package Bitrix\Scale *
 */
class ServersData
{
	/**
	 * @param $hostname
	 * @return array Server's params
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getServer($hostname)
	{
		if(strlen($hostname) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("hostname");

		$result = array();
		$servers = self::getList();

		if(isset($servers[$hostname]))
			$result = $servers[$hostname];

		return $result;
	}

	/**
	 * @return array List of servers & their params
	 */
	public static function getList()
	{
		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/wrapper_ansible_conf -o json");
		$serversData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($serversData, true);

			//mgmt server must be first
			if(isset($arData["params"]) && is_array($arData["params"]))
			{
				foreach($arData["params"] as $hostname => $server)
				{
					$server["BX_ENV_VER"] = static::getBxEnvVer($hostname);

					if(!$server["BX_ENV_VER"] || !Helper::checkBxEnvVersion($server["BX_ENV_VER"]))
						$server["BX_ENV_NEED_UPDATE"] = true;
					else
						$server["BX_ENV_NEED_UPDATE"] = false;

					$result[$hostname] = $server;
				}

				\sortByColumn($result, array( "host_id" => array(SORT_NUMERIC, SORT_ASC)));
			}
		}

		return $result;
	}

	/**
	 * @param $hostname
	 * @return array Server roles
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getServerRoles($hostname)
	{
		if(strlen($hostname) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("hostname");

		$result = array();
		$server = static::getServer($hostname);

		if(isset($server["roles"]))
			$result = $server["roles"];

		$result["SERVER"] = array();

		return $result;
	}

	public function getDbList($hostname)
	{
		if(strlen($hostname) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("hostname");

		$dbList = array();

		$action =  new Action("get_db_list", array(
			"START_COMMAND_TEMPLATE" => "sudo -u root /opt/webdir/bin/wrapper_ansible_conf -a dbs_list -H ".$hostname." -o json",
			"LOG_LEVEL" => Logger::LOG_LEVEL_DISABLE
			),
			"",
			array()
		);

		$action->start();
		$actRes = $action->getResult();

		if(isset($actRes["get_db_list"]["OUTPUT"]["DATA"]["params"]["dbs_list"][$hostname]["dbs_list"]))
			$dbList = $actRes["get_db_list"]["OUTPUT"]["DATA"]["params"]["dbs_list"][$hostname]["dbs_list"];

		if(is_array($dbList))
			$result = $dbList;
		else
			$result = array();

		return $result;
	}

	public function getBxEnvVer($hostname)
	{
		if(strlen($hostname) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("hostname");

		$action =  new Action("get_bxenv_ver", array(
				"START_COMMAND_TEMPLATE" => "sudo -u root /opt/webdir/bin/wrapper_ansible_conf -a bx_info -H ".$hostname." -o json",
				"LOG_LEVEL" => Logger::LOG_LEVEL_DISABLE
			),
			"",
			array()
		);

		$action->start();
		$actRes = $action->getResult();

		if(isset($actRes["get_bxenv_ver"]["OUTPUT"]["DATA"]["params"]["bx_variables"][$hostname]["bx_version"])
			&& $actRes["get_bxenv_ver"]["OUTPUT"]["DATA"]["params"]["bx_variables"][$hostname]["bx_version"] != "0"
		)
		{
			$result = $actRes["get_bxenv_ver"]["OUTPUT"]["DATA"]["params"]["bx_variables"][$hostname]["bx_version"];
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	public static function getGraphCategories($hostname)
	{
		$result = array();
		$roles = static::getServerRoles($hostname);

		foreach($roles as $roleId => $role)
			$result = array_merge($result, \Bitrix\Scale\RolesData::getGraphsCategories($roleId));

		return $result;
	}
}