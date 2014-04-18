<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class SectionValues extends BaseValues
{
	protected $section_id = 0;
	public function __construct($iblock_id, $section_id)
	{
		parent::__construct($iblock_id);
		$this->section_id = intval($section_id);
	}
	public function getValueTableName()
	{
		return "b_iblock_section_iprop";
	}
	public function getType()
	{
		return "S";
	}
	public function getId()
	{
		return $this->section_id;
	}
	public function  createTemplateEntity()
	{
		return new \Bitrix\Iblock\Template\Entity\Section($this->section_id);
	}

	public function getParents()
	{
		$parents = array();
		$sectionList = \Bitrix\Iblock\SectionTable::getList(array(
			"select" => array("IBLOCK_SECTION_ID"),
			"filter" => array("=ID" => $this->section_id),
		));
		$section = $sectionList->fetch();
		if ($section && $section["IBLOCK_SECTION_ID"] > 0)
			$parents[] = new SectionValues($this->iblock_id, $section["IBLOCK_SECTION_ID"]);
		else
			$parents[] = new IblockValues($this->iblock_id);
		return $parents;
	}
	public function queryValues()
	{
		$result = array();
		if ($this->hasTemplates())
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$query = $connection->query("
				SELECT
					P.ID
					,P.CODE
					,P.TEMPLATE
					,P.ENTITY_TYPE
					,P.ENTITY_ID
					,IP.VALUE
				FROM
					b_iblock_section_iprop IP
					INNER JOIN b_iblock_iproperty P ON P.ID = IP.IPROP_ID
				WHERE
					IP.IBLOCK_ID = ".$this->iblock_id."
					AND IP.SECTION_ID = ".$this->section_id."
			");

			while ($row = $query->fetch())
			{
				$result[$row["CODE"]] = $row;
			}

			if (empty($result))
			{
				$result = parent::queryValues();
				foreach ($result as $row)
				{
					$connection->add("b_iblock_section_iprop", array(
						"IBLOCK_ID" => $this->iblock_id,
						"SECTION_ID" => $this->section_id,
						"IPROP_ID" => $row["ID"],
						"VALUE" => $row["VALUE"],
					));
				}
			}
		}
		return $result;
	}
	function clearValues()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sectionList = \Bitrix\Iblock\SectionTable::getList(array(
			"select" => array("LEFT_MARGIN", "RIGHT_MARGIN"),
			"filter" => array("=ID" => $this->section_id),
		));
		$section = $sectionList->fetch();
		if ($section)
		{
			$connection->query("
				DELETE FROM b_iblock_element_iprop
				WHERE IBLOCK_ID = ".$this->iblock_id."
				AND ELEMENT_ID in (
					SELECT BSE.IBLOCK_ELEMENT_ID
					FROM b_iblock_section_element BSE
					INNER JOIN b_iblock_section BS ON BSE.IBLOCK_SECTION_ID = BS.ID AND BSE.ADDITIONAL_PROPERTY_ID IS NULL
					WHERE BS.IBLOCK_ID = ".$this->iblock_id."
					AND BS.LEFT_MARGIN <= ".$section["RIGHT_MARGIN"]."
					AND BS.RIGHT_MARGIN >= ".$section["LEFT_MARGIN"]."
				)
			");
			$connection->query("
				DELETE FROM b_iblock_section_iprop
				WHERE IBLOCK_ID = ".$this->iblock_id."
				AND SECTION_ID in (
					SELECT BS.ID
					FROM b_iblock_section BS
					WHERE BS.IBLOCK_ID = ".$this->iblock_id."
					AND BS.LEFT_MARGIN <= ".$section["RIGHT_MARGIN"]."
					AND BS.RIGHT_MARGIN >= ".$section["LEFT_MARGIN"]."
				)
			");
		}
	}
}
