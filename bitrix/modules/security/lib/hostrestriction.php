<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage security
* @copyright 2001-2013 Bitrix
*/

namespace Bitrix\Security;

use Bitrix\Main\Config;
use Bitrix\Main\Context;
use Bitrix\Main\Data;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;

/**
 * Class HostRestriction
 * @since 14.0.6
 * @example tests/security/hosts/basic.php
 * @package Bitrix\Security
 */
class HostRestriction
{
	const ACTION_REDIRECT = 'redirect';
	const ACTION_STOP = 'stop';

	private $optionPrefix = 'restriction_hosts_';
	private $cacheId = 'security_restriction_hosts';
	private $cacheTtl = 31536000; //one year
	private $action = 'stop';
	private $actionOptions = array();
	private $isLogNeeded = true;
	private $hosts = null;
	private $validActions = array(
		self::ACTION_REDIRECT,
		self::ACTION_STOP
	);
	private $validationRegExp = null;
	private $isActive = null;

	public static function onPageStart()
	{
		if (\CSecuritySystemInformation::isCliMode())
			return;

		/** @var HostRestriction $instance */
		$instance = new static;
		$instance->process();
	}

	public function __construct()
	{
		$this->hosts = Config\Option::get('security', $this->optionPrefix.'hosts', '');
		$this->action = Config\Option::get('security', $this->optionPrefix.'action', '');
		$this->actionOptions = unserialize(Config\Option::get('security', $this->optionPrefix.'action_options', '{}'));
		$this->isLogNeeded = Config\Option::get('security', $this->optionPrefix.'logging', false);
	}

	/**
	 * @param string $host
	 */
	public function process($host = null)
	{
		if (is_null($host))
			$host = $this->getTargetHost();

		if ($this->isValidHost($host))
			return;

		if ($this->isLogNeeded)
			$this->log($host);

		$this->doActions();

	}

	/**
	 * @param string $host
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function isValidHost($host)
	{
		return (bool) (
			is_string($host)
			&& $host !== ''
			&& preg_match($this->getValidationRegExp(), $host) > 0
		);
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return array(
			'hosts' => $this->hosts,
			'action' => $this->action,
			'action_options' => $this->actionOptions,
			'logging' => $this->isLogNeeded,
			'active' => is_null($this->isActive)? $this->getActive(): $this->isActive
		);
	}

	/**
	 * @param array $properties
	 * @return $this
	 */
	public function setProperties(array $properties)
	{
		if (isset($properties['hosts']))
		{
			$this->setHosts($properties['hosts']);
		}

		if (isset($properties['action']))
		{
			if (isset($properties['action_options']))
			{
				$this->setAction($properties['action'], $properties['action_options']);
			}
			else
			{
				$this->setAction($properties['action']);
			}
		}

		if (isset($properties['logging']))
		{
			$this->setLogging($properties['logging']);
		}

		if (isset($properties['active']))
		{
			$this->setActive($properties['active']);
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @return array
	 */
	public function getActionOptions()
	{
		return $this->actionOptions;
	}

	/**
	 * @param string $action
	 * @param array $options
	 * @return $this
	 * @throws \Bitrix\Security\LogicException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function setAction($action, $options = array())
	{
		if (!$action)
			throw new ArgumentNullException('action');

		if (!is_string($action))
			throw new ArgumentTypeException('action', 'string');

		if (!in_array($action, $this->validActions))
			throw new ArgumentOutOfRangeException('action');

		if ($action === self::ACTION_REDIRECT)
		{
			if (!is_set($options['host']) || !$options['host'])
			{
				throw new LogicException('options[host] not present', 'SECURITY_HOSTS_EMPTY_HOST_ACTION');
			}

			if (!preg_match('~^https?://~', $options['host']))
				throw new LogicException('invalid redirecting host present in options[host]', 'SECURITY_HOSTS_INVALID_HOST_ACTION');

		}


		$this->action = $action;
		$this->actionOptions = $options;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getLogging()
	{
		return $this->isLogNeeded;
	}

	/**
	 * @param bool $isLogNeeded
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function setLogging($isLogNeeded = true)
	{
		if (!is_bool($isLogNeeded))
			throw new ArgumentTypeException('isLogNeeded', 'bool');

		$this->isLogNeeded = $isLogNeeded;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getActive()
	{
		if (is_null($this->isActive))
			$this->isActive = $this->isBound();

		return $this->isActive;
	}

	/**
	 * @param bool $isActive
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @return $this
	 */
	public function setActive($isActive = false)
	{
		if (!is_bool($isActive))
			throw new ArgumentTypeException('isActive', 'bool');

		$this->isActive = $isActive;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHosts()
	{
		return $this->hosts;
	}

	/**
	 * @param string $hosts
	 * @param bool $ignoreChecking
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function setHosts($hosts, $ignoreChecking = false)
	{
		if (!is_string($hosts))
			throw new ArgumentTypeException('host', 'string');

		if (!$ignoreChecking)
			$this->checkNewHosts($hosts);

		$this->hosts = $hosts;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getValidationRegExp()
	{
		if ($this->validationRegExp)
			return $this->validationRegExp;

		$cache = Data\Cache::createInstance();
		if($cache->initCache($this->cacheTtl, $this->cacheId) )
		{
			$this->validationRegExp = $cache->getVars();
		}
		else
		{
			$this->validationRegExp = $this->genValidationRegExp($this->hosts);
			$cache->startDataCache();
			$cache->endDataCache($this->validationRegExp);
		}

		return $this->validationRegExp;
	}

	public function save()
	{
		Config\Option::set('security', $this->optionPrefix.'hosts', $this->hosts, '');
		Config\Option::set('security', $this->optionPrefix.'action', $this->action, '');
		Config\Option::set('security', $this->optionPrefix.'action_options', serialize($this->actionOptions), '');
		Config\Option::set('security', $this->optionPrefix.'logging', $this->isLogNeeded, '');
		if (!is_null($this->isActive))
		{

			if ($this->isActive)
			{
				EventManager::getInstance()
					->registerEventHandler('main', 'OnPageStart', 'security', get_class($this), 'onPageStart');
			}
			else
			{
				EventManager::getInstance()
					->unRegisterEventHandler('main', 'OnPageStart', 'security', get_class($this), 'onPageStart');
			}
		}
		Data\Cache::createInstance()->clean($this->cacheId);
	}

	/**
	 * @return bool
	 */
	protected function isBound()
	{
		$handlers = EventManager::getInstance()->findEventHandlers('main', 'OnPageStart', array('security'));

		foreach($handlers as $handler)
		{
			if ($handler['TO_CLASS'] === get_class($this))
				return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	protected function getTargetHost()
	{
		static $host = null;
		if (is_null($host))
			$host = Context::getCurrent()->getServer()->getHttpHost();

		return $host;
	}

	/**
	 * @param string $host
	 * @return bool
	 */
	protected function log($host)
	{
		return \CSecurityEvent::getInstance()->doLog('SECURITY', 'SECURITY_HOST_RESTRICTION', 'HTTP_HOST', $host);
	}

	protected function doActions()
	{
		switch($this->action)
		{
			case self::ACTION_STOP:
				include Loader::getLocal('/admin/security_403.php');
				die();
				break;
			case self::ACTION_REDIRECT:
				localRedirect($this->actionOptions['host'], true);
				break;
			default:
				trigger_error('Unknown action', E_USER_WARNING);
		}
	}

	/**
	 * @param string $hosts
	 * @return string
	 */
	protected function genValidationRegExp($hosts)
	{
		$hosts = trim($hosts);
		$hosts = preg_quote($hosts);
		$hosts = preg_replace(
			array('~\#.*~', '~\\\\\*~', '~\s+~s'),
			array('',       '.*',       '|'),
			$hosts
		);

		return "#^\s*($hosts)(:\d+)?\s*$#i";
	}

	/**
	 * @param string $hosts
	 * @throws \Bitrix\Security\LogicException
	 */
	protected function checkNewHosts($hosts)
	{
		$this->validationRegExp = $this->genValidationRegExp($hosts);

		if (!preg_match($this->validationRegExp, $this->getTargetHost()))
			throw new LogicException('Current host blocked', 'SECURITY_HOSTS_SELF_BLOCK');

		if (preg_match($this->validationRegExp, 'some-invalid-host.com'))
			throw new LogicException('Any host passed restrictions', 'SECURITY_HOSTS_ANY_HOST');
	}
}