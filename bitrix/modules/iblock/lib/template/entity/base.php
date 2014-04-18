<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class Base
{
	protected $id = null;
	protected $fields = null;
	protected $fieldMap = array();
	public function __construct($id)
	{
		$this->id = $id;
	}
	public function resolve($entity)
	{
		if ($entity === "this")
			return $this;
		else
			return new Base(0);
	}
	public function setFields(array $fields)
	{
		$this->fields = $fields;
	}
	public function getField($fieldName)
	{
		if (!$this->loadFromDatabase())
			return "";

		if (!isset($this->fieldMap[$fieldName]))
			return "";

		$fieldName = $this->fieldMap[$fieldName];
		if (!isset($this->fields[$fieldName]))
			return "";

		$fieldValue = $this->fields[$fieldName];
		if (is_array($fieldValue))
		{
			$result = array();
			foreach($fieldValue as $key => $value)
			{
				if ($value instanceof LazyValueLoader)
					$result[$key] = $value->getValue();
				else
					$result[$key] = $value;

			}
			return $result;
		}
		else
		{
			if ($fieldValue instanceof LazyValueLoader)
			{
				return $fieldValue->getValue();
			}
			return $this->fields[$fieldName];
		}
	}
	protected function loadFromDatabase()
	{
		if (!isset($this->fields))
		{
			$this->fields = array();
		}
		return true;//is_array($this->fields);
	}
	protected function addField($fieldName, $internalName, $value)
	{
		if (!isset($this->fields[$internalName]))
			$this->fields[$internalName] = $value;
		$this->fieldMap[strtolower($fieldName)] = $internalName;
	}
}

class LazyValueLoader
{
	protected $value = null;
	protected $key = null;
	function __construct($key)
	{
		$this->key = $key;
	}
	public function __toString()
	{
		if (!isset($this->value))
			$this->value = $this->load();
		return $this->value;
	}
	public function getValue()
	{
		if (!isset($this->value))
			$this->value = $this->load();
		return $this->value;
	}
	protected function load()
	{
		return "";
	}
}

