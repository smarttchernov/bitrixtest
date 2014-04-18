<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

use Bitrix\Iblock\Template\Entity\Element;

class ElementTemplates extends BaseTemplate
{
	function __construct($iblock_id, $element_id)
	{
		$entity = new ElementValues($iblock_id, $element_id);
		parent::__construct($entity);
	}
}