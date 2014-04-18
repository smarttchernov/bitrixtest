<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("T_IBLOCK_DESC_CSC_LIST"),
	"DESCRIPTION" => GetMessage("T_IBLOCK_DESC_CSC_DESC"),
	"ICON" => "/images/like_buttons.gif",
	"CACHE_PATH" => "Y",
	"SORT" => 40,
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "catalog",
			"NAME" => GetMessage("T_IBLOCK_DESC_CATALOG"),
			"SORT" => 30,
			"CHILD" => array(
				"ID" => "catalog_socnet_comments",
			),
		),
	),
);

?>