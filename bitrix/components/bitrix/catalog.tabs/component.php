<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(isset($arParams["ID"]))
	$arResult["ID"] = $arParams["ID"];
else
	$arResult["ID"] ="cat_tab_".$this->randString();

if(isset($arParams["WIDTH"]) && intval($arParams["WIDTH"]) > 0)
	$arResult["WIDTH"] = intval($arParams["WIDTH"]);

$this->IncludeComponentTemplate();
?>