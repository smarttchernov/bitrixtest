<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
global $DB;
/** @global CUser $USER */
global $USER;
/** @global CMain $APPLICATION */
global $APPLICATION;

if (!empty($_REQUEST["edit"]))
	$componentPage = "form";
else
	$componentPage = "list";

$arParams["EDIT_URL"] = $APPLICATION->GetCurPage("", array("edit", "delete", "CODE"));
$arParams["LIST_URL"] = $arParams["EDIT_URL"];

$this->IncludeComponentTemplate($componentPage);
?>