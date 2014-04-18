<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

abstract class BaseValues
{
	protected $iblock_id = null;
	protected $values = false;
	public function __construct($iblock_id)
	{
		$this->iblock_id = intval($iblock_id);
	}
	public function getIblockId()
	{
		return $this->iblock_id;
	}

	abstract public function getType();

	abstract public function getId();

	/**
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	abstract public function  createTemplateEntity();

	/**
	 * @return array[]\Bitrix\Iblock\InheritedProperty\BaseValues
	 */
	public function getParents()
	{
		return array();
	}

	public function getParent()
	{
		$parents = $this->getParents();
		return $parents[0];
	}

	public function getValues()
	{
		if ($this->values === false)
			$this->values = $this->queryValues();

		$result = array();
		foreach ($this->values as $CODE => $row)
		{
			$result[$CODE] = \Bitrix\Main\Text\String::htmlEncode($row["VALUE"]);
		}
		return $result;
	}

	public function getValue($propertyCode)
	{
		if ($this->values === false)
			$this->values = $this->queryValues();

		if (isset($this->values[$propertyCode]))
			return \Bitrix\Main\Text\String::htmlEncode($this->values[$propertyCode]["VALUE"]);
		else
			return "";
	}

	/**
	 * @return array[string][string]string
	 */
	public function queryValues()
	{
		$templateInstance = new BaseTemplate($this);
		$templates = $templateInstance->findTemplates();
		foreach ($templates as $CODE => $row)
		{
			$templates[$CODE]["VALUE"] = \Bitrix\Iblock\Template\Engine::process($this->createTemplateEntity(), $row["TEMPLATE"]);
		}
		return $templates;
	}

	public function hasTemplates()
	{
		$templateInstance = new BaseTemplate($this);
		return $templateInstance->hasTemplates($this);
	}
	/**
	 * Clears entity values DB cache
	 *
	 * @return void
	 */
	abstract function clearValues();

	/**
	 * Must be called on template delete.
	 *
	 * @param int $ipropertyId
	 * @return void
	 */
	public function deleteValues($ipropertyId)
	{
		$ipropertyId = intval($ipropertyId);
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("
			DELETE FROM b_iblock_iblock_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
		$connection->query("
			DELETE FROM b_iblock_section_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
		$connection->query("
			DELETE FROM b_iblock_element_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
	}
}
