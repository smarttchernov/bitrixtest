<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class BaseTemplate
{
	/** @var \Bitrix\Iblock\InheritedProperty\BaseValues|null */
	protected $entity = null;

	function __construct(BaseValues $entity)
	{
		$this->entity = $entity;
	}
	public function getValuesEntity()
	{
		return $this->entity;
	}
	public function set(array $templates)
	{
		$templateList = \Bitrix\Iblock\InheritedPropertyTable::getList(array(
			"select" => array("ID", "CODE", "TEMPLATE"),
			"filter" => array(
				"=IBLOCK_ID" => $this->entity->getIblockId(),
				"=ENTITY_TYPE" => $this->entity->getType(),
				"=ENTITY_ID" => $this->entity->getId(),
			),
		));
		array_map("trim", $templates);
		while ($row = $templateList->fetch())
		{
			$CODE = $row["CODE"];
			if (array_key_exists($CODE, $templates))
			{
				if ($templates[$CODE] !== $row["TEMPLATE"])
				{
					if ($templates[$CODE] != "")
						\Bitrix\Iblock\InheritedPropertyTable::update($row["ID"], array(
							"TEMPLATE" => $templates[$CODE],
						));
					else
						\Bitrix\Iblock\InheritedPropertyTable::delete($row["ID"]);

					$this->entity->deleteValues($row["ID"]);
				}
				unset($templates[$CODE]);
			}
		}

		if (!empty($templates))
		{
			foreach ($templates as $CODE => $TEMPLATE)
			{
				if ($TEMPLATE != "")
				{
					\Bitrix\Iblock\InheritedPropertyTable::add(array(
						"IBLOCK_ID" => $this->entity->getIblockId(),
						"CODE" => $CODE,
						"ENTITY_TYPE" => $this->entity->getType(),
						"ENTITY_ID" => $this->entity->getId(),
						"TEMPLATE" => $TEMPLATE,
					));
				}
			}
			$this->entity->clearValues();
		}
	}

	public function get(BaseValues $entity = null)
	{
		if ($entity === null)
			$entity = $this->entity;

		$result = array();
		$templateList = \Bitrix\Iblock\InheritedPropertyTable::getList(array(
			"select" => array("ID", "CODE", "TEMPLATE", "ENTITY_TYPE", "ENTITY_ID"),
			"filter" => array(
				"=IBLOCK_ID" => $entity->getIblockId(),
				"=ENTITY_TYPE" => $entity->getType(),
				"=ENTITY_ID" => $entity->getId(),
			),
		));
		while ($row = $templateList->fetch())
		{
			$result[$row["CODE"]] = $row;
		}

		return $result;
	}

	public function hasTemplates(BaseValues $entity)
	{
		static $cache = array();
		$iblockId = $entity->getIblockId();
		if (!isset($cache[$iblockId]))
		{
			$templateList = \Bitrix\Iblock\InheritedPropertyTable::getList(array(
				"select" => array("ID"),
				"filter" => array(
					"=IBLOCK_ID" => $iblockId,
				),
				"limit" => 1,
			));
			$cache[$iblockId] = is_array($templateList->fetch());
		}
		return $cache[$iblockId];
	}

	public function delete()
	{
		$templateList = \Bitrix\Iblock\InheritedPropertyTable::getList(array(
			"select" => array("ID"),
			"filter" => array(
				"=IBLOCK_ID" => $this->entity->getIblockId(),
				"=ENTITY_TYPE" => $this->entity->getType(),
				"=ENTITY_ID" => $this->entity->getId(),
			),
		));

		while ($row = $templateList->fetch())
		{
			\Bitrix\Iblock\InheritedPropertyTable::delete($row["ID"]);
		}
	}

	protected function findTemplatesRecursive(BaseValues $entity, &$templates)
	{
		foreach ($this->get($entity) as $CODE => $templateData)
		{
			if (!array_key_exists($CODE, $templates))
				$templates[$CODE] = $templateData;
		}

		foreach ($entity->getParents() as $parent)
		{
			$this->findTemplatesRecursive($parent, $templates);
		}
	}

	public function findTemplates()
	{
		$templates = array();
		if ($this->hasTemplates($this->entity))
		{
			$this->findTemplatesRecursive($this->entity, $templates);
			foreach ($templates as $CODE => $row)
			{
				if ($row["ENTITY_TYPE"] == $this->entity->getType() && $row["ENTITY_ID"] == $this->entity->getId())
					$templates[$CODE]["INHERITED"] = "N";
				else
					$templates[$CODE]["INHERITED"] = "Y";
			}
		}
		return $templates;
	}
}