<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class ElementSkuProperty extends Base
{
	protected $iblock_id = 0;
	protected $properties = array();
	public function __construct($id)
	{
		parent::__construct($id);
	}
	public function setIblockId($iblockId)
	{
		$this->iblock_id = intval($iblockId);
	}
	protected function loadFromDatabase()
	{
		if (!isset($this->fields) && $this->iblock_id > 0 && is_array($this->id))
		{
			$this->fields = array();
			foreach($this->id as $id)
			{
				if ($id > 0)
				{
					$propertyList = \CIBlockElement::getProperty(
						$this->iblock_id,
						$id,
						array("sort" => "asc"),
						array("EMPTY" => "N")
					);
					while ($property = $propertyList->fetch())
					{
						if ($property["VALUE_ENUM"] != "")
						{
							$value = $property["VALUE_ENUM"];
						}
						elseif ($property["PROPERTY_TYPE"] === "E")
						{
							$value = new ElementPropertyElement($property["VALUE"]);
						}
						elseif ($property["PROPERTY_TYPE"] === "G")
						{
							$value = new ElementPropertySection($property["VALUE"]);
						}
						else
						{
							if(strlen($property["USER_TYPE"]))
							{
								$value = new ElementPropertyUserField($property["VALUE"], $property);
							}
							else
							{
								$value = $property["VALUE"];
							}
						}

						$this->fields[$property["ID"]][] = $value;
						$this->fieldMap[$property["ID"]] = $property["ID"];
						if ($property["CODE"] != "")
							$this->fieldMap[strtolower($property["CODE"])] = $property["ID"];
					}
				}
			}
		}
		return is_array($this->fields);
	}
}
