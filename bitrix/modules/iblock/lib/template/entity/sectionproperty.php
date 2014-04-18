<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class SectionProperty extends Base
{
	protected $iblock_id = 0;
	public function __construct($id)
	{
		parent::__construct($id);
	}
	public function setIblockId($iblockId)
	{
		$this->iblock_id = intval($iblockId);
	}
	public function resolve($entity)
	{
		return parent::resolve($entity);
	}
	public function setFields(array $fields)
	{
		parent::setFields($fields);
		if (
			is_array($this->fields)
			&& $this->iblock_id > 0
		)
		{
			foreach ($this->fields as $id => $value)
			{
				if (substr($id, 0, 3) === "UF_")
				{
					$propertyCode = $id;
					$fieldCode = strtolower(substr($id, 3));
					$this->fieldMap[$fieldCode] = $propertyCode;
				}
			}
		}
	}
	protected function loadFromDatabase()
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		if (!isset($this->fields) && $this->iblock_id > 0)
		{
			$userFields = $USER_FIELD_MANAGER->getUserFields(
				"IBLOCK_".$this->iblock_id."_SECTION",
				$this->id
			);
			foreach ($userFields as $id => $uf)
			{
				$this->addField(substr($id, 3), $id, $uf["VALUE"]);
			}
		}
		return is_array($this->fields);
	}
}
