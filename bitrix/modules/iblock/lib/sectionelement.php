<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class SectionElementTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_iblock_section_element';
	}

	public static function getMap()
	{
		return array(
			'IBLOCK_SECTION_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_SECTION_ELEMENT_ENTITY_IBLOCK_SECTION_ID_FIELD'),
			),
			'IBLOCK_SECTION' => array(
				'data_type' => 'Section',
				'reference' => array('=this.IBLOCK_SECTION_ID' => 'ref.ID'),
			),
			'IBLOCK_ELEMENT_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_SECTION_ELEMENT_ENTITY_IBLOCK_ELEMENT_ID_FIELD'),
			),
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'Element',
				'reference' => array('=this.IBLOCK_ELEMENT_ID' => 'ref.ID'),
				'title' => Loc::getMessage('IBLOCK_SECTION_ELEMENT_ENTITY_IBLOCK_ELEMENT_FIELD'),
			),
			'ADDITIONAL_PROPERTY_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_SECTION_ELEMENT_ENTITY_ADDITIONAL_PROPERTY_ID_FIELD'),
			)
		);
	}
}
