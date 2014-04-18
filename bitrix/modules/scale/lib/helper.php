<?
namespace Bitrix\Scale;

/**
* Class RolesData
* @package Bitrix\Scale
*/
class Helper
{
	const BX_ENV_MIN_VERSION = "5.0-0";

	public static function checkBxEnvVersion($version = false)
	{
		if(!$version)
			$version = getenv('BITRIX_VA_VER');

		return version_compare($version, self::BX_ENV_MIN_VERSION , '>=');
	}

	public static function nbsp($str)
	{
		return str_replace(" ", "&nbsp;",$str);
	}
}
