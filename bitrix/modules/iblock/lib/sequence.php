<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class SequenceTable
 * 
 * Fields:
 * <ul>
 * <li> IBLOCK_ID int mandatory
 * <li> CODE string(50) mandatory
 * <li> SEQ_VALUE int optional
 * <li> IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 **/

class SequenceTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_iblock_sequence';
	}

	public static function getMap()
	{
		return array(
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_SEQUENCE_ENTITY_IBLOCK_ID_FIELD'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('IBLOCK_SEQUENCE_ENTITY_CODE_FIELD'),
			),
			'SEQ_VALUE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_SEQUENCE_ENTITY_SEQ_VALUE_FIELD'),
			),
			'IBLOCK' => array(
				'data_type' => 'Bitrix\Iblock\Iblock',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID'),
			),
		);
	}
	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
}