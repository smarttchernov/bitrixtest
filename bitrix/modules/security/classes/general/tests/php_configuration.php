<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecurityPhpConfigurationTest
 * @since 12.5.0
 */
class CSecurityPhpConfigurationTest
	extends CSecurityBaseTest
{
	protected $internalName = "PhpConfigurationTest";

	protected $tests = array(
		"phpEntropy" => array(
			"method" => "checkPhpEntropy"
		),
		"phpInclude" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("allow_url_include"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_INCLUDE",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"phpFopen" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("allow_url_fopen"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_FOPEN",
			"critical" => CSecurityCriticalLevel::MIDDLE
		),
		"aspTags" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("asp_tags"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_ASP",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"httpOnly" => array(
			"method" => "isPhpConfVarOn",
			"params" => array("session.cookie_httponly"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_HTTPONLY",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"cookieOnly" => array(
			"method" => "isPhpConfVarOn",
			"params" => array("session.use_only_cookies"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_COOKIEONLY",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"mbstringSubstitute" => array(
			"method" => "checkMbstringSubstitute",
			"params" => array(),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_MBSTRING_SUBSTITUTE",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
	);

	public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
	}

	/**
	 * Check php session entropy
	 * @return bool
	 */
	protected function checkPhpEntropy()
	{
		if(self::isRunOnWin() && version_compare(phpversion(),"5.3.3","<"))
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_LOW_PHP_VERSION_ENTROPY", CSecurityCriticalLevel::MIDDLE);
			return false;
		}
		elseif(!self::checkPhpEntropyConfigs())
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_PHP_ENTROPY", CSecurityCriticalLevel::MIDDLE);
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 */
	protected function checkPhpEntropyConfigs()
	{
		$entropyFile = ini_get("session.entropy_file");
		$entropyLength = ini_get("session.entropy_length");
		if(in_array($entropyFile, array("/dev/random", "/dev/urandom"), true))
		{
			if($entropyLength >= 128 || self::isRunOnWin())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return bool
	 */
	protected function checkMbstringSubstitute()
	{
		return (bool) (
			!extension_loaded('mbstring')
			|| $this->isPhpConfVarNotEquals('mbstring.substitute_character', 'none')
		);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	protected function isPhpConfVarOff($name)
	{
		return (intval(ini_get($name)) == 0 || strtolower(trim(ini_get($name))) == "off");
	}

	/**
	 * @param string $name
	 * @return bool
	 * @since 14.0.0
	 */
	protected function isPhpConfVarOn($name)
	{
		return (intval(ini_get($name)) == 1 || strtolower(trim(ini_get($name))) == "on");
	}

	/**
	 * @param string $name
	 * @param int|string $value
	 * @return bool
	 */
	protected function isPhpConfVarEquals($name, $value)
	{
		return ini_get($name) == $value;
	}

	/**
	 * @param string $name
	 * @param int|string $value
	 * @return bool
	 */
	protected function isPhpConfVarNotEquals($name, $value)
	{
		return ini_get($name) != $value;
	}

}