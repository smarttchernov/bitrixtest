<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$requiredModules = array('highloadblock');

foreach ($requiredModules as $requiredModule)
{
	if (!CModule::IncludeModule($requiredModule))
	{
		ShowError(GetMessage("F_NO_MODULE"));
		return 0;
	}
}

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

// hlblock info
$hlblock_id = $arParams['BLOCK_ID'];

$hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();

if (empty($hlblock))
{
	ShowError('404');
	return 0;
}

$entity = HL\HighloadBlockTable::compileEntity($hlblock);

// uf info
$fields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_'.$hlblock['ID'], 0, LANGUAGE_ID);

// row data
$main_query = new Entity\Query($entity);
$main_query->setSelect(array('*'));
$main_query->setFilter(array('=ID' => $arParams['ROW_ID']));

$result = $main_query->exec();
$result = new CDBResult($result);
$row = $result->Fetch();

$arResult['fields'] = $fields;
$arResult['row'] = $row;


$this->IncludeComponentTemplate();