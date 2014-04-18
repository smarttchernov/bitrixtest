<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class SectionTemplates extends BaseTemplate
{
	function __construct($iblock_id, $section_id)
	{
		$entity = new SectionValues($iblock_id, $section_id);
		parent::__construct($entity);
	}
}