<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class IblockTemplates extends BaseTemplate
{
	function __construct($iblock_id)
	{
		$entity = new IblockValues($iblock_id);
		parent::__construct($entity);
	}
}