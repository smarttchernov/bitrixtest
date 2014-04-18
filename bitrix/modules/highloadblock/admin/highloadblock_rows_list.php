<?php

// admin initialization
define("ADMIN_MODULE_NAME", "highloadblock");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

if (!CModule::IncludeModule(ADMIN_MODULE_NAME))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

use Bitrix\Highloadblock as HL;

// get entity settings
$hlblock = null;

if (isset($_REQUEST['ENTITY_ID']) && $_REQUEST['ENTITY_ID'] > 0)
{
	$hlblock = HL\HighloadBlockTable::getById($_REQUEST['ENTITY_ID'])->fetch();
}

if (empty($hlblock))
{
	// 404
	if ($_REQUEST["mode"] == "list")
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	}
	else
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	}

	echo GetMessage('HLBLOCK_ADMIN_ROWS_LIST_NOT_FOUND');

	if ($_REQUEST["mode"] == "list")
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
	}
	else
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	}

	die();
}

$APPLICATION->SetTitle(GetMessage('HLBLOCK_ADMIN_ROWS_LIST_PAGE_TITLE', array('#NAME#' => $hlblock['NAME'])));

$entity = HL\HighloadBlockTable::compileEntity($hlblock);

$entity_data_class = $entity->getDataClass();
$entity_table_name = $hlblock['TABLE_NAME'];

$sTableID = 'tbl_'.$entity_table_name;
$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arHeaders = array(array(
	'id' => 'ID',
	'content' => 'ID',
	'sort' => 'ID',
	'default' => true
));

$USER_FIELD_MANAGER->AdminListAddHeaders('HLBLOCK_'.$hlblock['ID'], $arHeaders);

// show all by default
foreach ($arHeaders as &$arHeader)
{
	$arHeader['default'] = true;
}
unset($arHeader);

$lAdmin->AddHeaders($arHeaders);

if (!in_array($by, $lAdmin->GetVisibleHeaderColumns(), true))
{
	$by = 'ID';
}

// select data
$rsData = $entity_data_class::getList(array(
	"select" => $lAdmin->GetVisibleHeaderColumns(),
	"order" => array($by => strtoupper($order))
));

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

if ($_REQUEST["mode"] !== "list")
{
	// menu
	$aMenu = array(
		array(
			"TEXT"	=> GetMessage('HLBLOCK_ADMIN_ROWS_ADD_NEW_BUTTON'),
			"TITLE"	=> GetMessage('HLBLOCK_ADMIN_ROWS_ADD_NEW_BUTTON'),
			"LINK"	=> "highloadblock_row_edit.php?ENTITY_ID=".intval($_REQUEST['ENTITY_ID'])."&lang=".LANGUAGE_ID,
			"ICON"	=> "btn_new",
		),
		array(
			"TEXT"	=> GetMessage('HLBLOCK_ADMIN_ROWS_EDIT_ENTITY'),
			"TITLE"	=> GetMessage('HLBLOCK_ADMIN_ROWS_EDIT_ENTITY'),
			"LINK"	=> "highloadblock_entity_edit.php?ID=".$hlblock['ID']."&lang=".LANGUAGE_ID,
			"ICON"	=> "btn_edit",
		)
	);

	$context = new CAdminContextMenu($aMenu);
}

// build list
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES")));
while($arRes = $rsData->NavNext(true, "f_"))
{
	$row = $lAdmin->AddRow($f_ID, $arRes);
	$USER_FIELD_MANAGER->AddUserFields('HLBLOCK_'.$hlblock['ID'], $arRes, $row);

	$can_edit = true;

	$arActions = Array();

	$arActions[] = array(
		"ICON" => "edit",
		"TEXT" => GetMessage($can_edit ? "MAIN_ADMIN_MENU_EDIT" : "MAIN_ADMIN_MENU_VIEW"),
		"ACTION" => $lAdmin->ActionRedirect("highloadblock_row_edit.php?ENTITY_ID=".$hlblock['ID'].'&ID='.$f_ID),
		"DEFAULT" => true
	);

	$arActions[] = array(
		"ICON"=>"delete",
		"TEXT" => GetMessage("MAIN_ADMIN_MENU_DELETE"),
		"ACTION" => "if(confirm('".GetMessageJS('HLBLOCK_ADMIN_DELETE_ROW_CONFIRM')."')) ".
			$lAdmin->ActionRedirect("highloadblock_row_edit.php?action=delete&ENTITY_ID=".$hlblock['ID'].'&ID='.$f_ID.'&'.bitrix_sessid_get())
	);

	$row->AddActions($arActions);

	// deny group operations (hide checkboxes)
	$row->pList->bCanBeEdited = false;
}


// view

if ($_REQUEST["mode"] == "list")
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
}
else
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$context->Show();
}

$lAdmin->CheckListMode();

$lAdmin->DisplayList();


if ($_REQUEST["mode"] == "list")
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
}
else
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}