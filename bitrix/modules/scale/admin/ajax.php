<?
/**
 * @global CUser $USER
 * @global CMain $APPLICATION
 */

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$arResult = array(
	"ERROR" => ""
);

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

if (!\Bitrix\Main\Loader::includeModule('scale'))
	$arResult["ERROR"] = Loc::getMessage("SCALE_AJAX_MODULE_NOT_INSTALLED");

$result = false;

if(strlen($arResult["ERROR"]) <= 0 && $USER->IsAdmin() && check_bitrix_sessid())
{
	$operation = isset($_REQUEST['params']['operation']) ? trim($_REQUEST['params']['operation']): '';

	switch ($operation)
	{
		case "start":
			$actionId = isset($_REQUEST['params']['actionId']) ? trim($_REQUEST['params']['actionId']): '';
			$serverHostname = isset($_REQUEST['params']['serverHostname']) ? trim($_REQUEST['params']['serverHostname']): "";
			$userParams = isset($_REQUEST['params']['userParams']) ? $_REQUEST['params']['userParams']: array();
			$freeParams = isset($_REQUEST['params']['freeParams']) ? $_REQUEST['params']['freeParams']: array();

			try
			{
				$action = \Bitrix\Scale\ActionsData::getActionObject($actionId, $serverHostname, $userParams, $freeParams);
			}
			catch(Exception $e)
			{
				$arResult["ERROR"] = $e->getMessage();
				$operation = ""; //don't execute enything else!
			}

			try
			{
				$result = $action->start();
				$arResult["ACTION_RESULT"] = $action->getResult();
			}
			catch(Exception $e)
			{
				$arResult["ERROR"] = $e->getMessage();
			}

			break;

		case "check_state":

			$bid = isset($_REQUEST['params']['bid']) ? trim($_REQUEST['params']['bid']): '';
			$arResult["ACTION_STATE"] = \Bitrix\Scale\ActionsData::getActionState($bid);

			if(!empty($arResult["ACTION_STATE"]))
				$result = true;

			break;

		case "get_monitoring_values":

			$servers = isset($_REQUEST['params']['servers']) ? $_REQUEST['params']['servers'] : array();
			$result = true;
			$arResult["MONITORING_DATA"] = array();

			foreach($servers as $hostname => $monitoringPartitions)
			{
				$arResult["MONITORING_DATA"][$hostname] = array();

				if(isset($monitoringPartitions["rolesIds"]) && is_array($monitoringPartitions["rolesIds"]))
				{
					foreach($monitoringPartitions["rolesIds"] as $roleId)
					{
						try
						{
							$arResult["MONITORING_DATA"][$hostname]["ROLES_LOADBARS"][$roleId] = \Bitrix\Scale\Monitoring::getLoadBarValue($hostname, $roleId);
						}
						catch(Exception $e)
						{
							$arResult["ERROR"] .= "\n".$e->getMessage();
							continue;
						}
					}
				}

				foreach($monitoringPartitions["monitoringParams"] as $categoryId => $category)
				{
					foreach($category as $paramId)
					{
						try
						{
							$arResult["MONITORING_DATA"][$hostname]["MONITORING_VALUES"][$categoryId][$paramId] = \Bitrix\Scale\Monitoring::getValue($hostname, $categoryId, $paramId);
						}
						catch(Exception $e)
						{
							$arResult["ERROR"] .= "\n".$e->getMessage();
							continue;
						}
					}
				}
			}

			break;
	}
}
else
{
	if(strlen($arResult["ERROR"]) <= 0)
		$arResult["ERROR"] = Loc::getMessage("SCALE_AJAX_ACCESS_DENIED");
}

if(!$result)
	$arResult["RESULT"] = "ERROR";
else
	$arResult["RESULT"] = "OK";

if(strtolower(SITE_CHARSET) != 'utf-8')
	$arResult = $APPLICATION->ConvertCharsetArray($arResult, SITE_CHARSET, 'utf-8');

die(json_encode($arResult));