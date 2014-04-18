<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class IblockValues extends BaseValues
{
	public function __construct($iblock_id)
	{
		parent::__construct($iblock_id);
	}
	public function getValueTableName()
	{
		return "b_iblock_iblock_iprop";
	}
	public function getType()
	{
		return "B";
	}
	public function getId()
	{
		return $this->iblock_id;
	}
	public function  createTemplateEntity()
	{
		return new \Bitrix\Iblock\Template\Entity\Iblock($this->iblock_id);
	}

	public function getParents()
	{
		return array();
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
					b_iblock_iblock_iprop IP
					INNER JOIN b_iblock_iproperty P ON P.ID = IP.IPROP_ID
				WHERE
					IP.IBLOCK_ID = ".$this->iblock_id."
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
					$connection->add("b_iblock_iblock_iprop", array(
						"IBLOCK_ID" => $this->iblock_id,
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
		$connection->query("
			DELETE FROM b_iblock_element_iprop
			WHERE IBLOCK_ID = ".$this->iblock_id."
		");
		$connection->query("
			DELETE FROM b_iblock_section_iprop
			WHERE IBLOCK_ID = ".$this->iblock_id."
		");
		$connection->query("
			DELETE FROM b_iblock_iblock_iprop
			WHERE IBLOCK_ID = ".$this->iblock_id."
		");
	}
}
