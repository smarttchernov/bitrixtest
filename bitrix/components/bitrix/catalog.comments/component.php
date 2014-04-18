<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/* Blog comments*/
if(!\Bitrix\Main\Loader::includeModule("iblock"))
{
	ShowError(GetMessage("IBLOCK_CSC_MODULE_NOT_INSTALLED"));
	return false;
}

if(!\Bitrix\Main\Loader::includeModule("blog"))
{
	ShowError(GetMessage("IBLOCK_CSC_MODULE_BLOG_NOT_INSTALLED"));
	return false;
}

$arResult["ELEMENT"] = array();
$arResult["ERRORS"] = array();

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 0;

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
$arParams["ELEMENT_ID"] = intval($arParams["~ELEMENT_ID"]);

//Handle case when ELEMENT_CODE used
if($arParams["ELEMENT_ID"] <= 0)
	$arParams["ELEMENT_ID"] = CIBlockFindTools::GetElementID(
		$arParams["ELEMENT_ID"],
		$arParams["ELEMENT_CODE"],
		false,
		false,
		array(
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"IBLOCK_LID" => SITE_ID,
			"IBLOCK_ACTIVE" => "Y",
			"ACTIVE_DATE" => "Y",
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => "Y",
		)
	);

//SELECT
$arSelect = array(
	"ID",
	"CODE",
	"IBLOCK_ID",
	"IBLOCK_SECTION_ID",
	"SECTION_PAGE_URL",
	"NAME",
	"ACTIVE",
	"PREVIEW_TEXT",
	"DETAIL_TEXT",
	"DETAIL_PAGE_URL",
	"PREVIEW_TEXT_TYPE",
	"DETAIL_TEXT_TYPE",
	"TAGS",
	"DATE_CREATE",
	"CREATED_BY",
	"PROPERTY_BLOG_POST_ID",
	"PROPERTY_BLOG_COMMENTS_CNT"
);

//WHERE
$arFilter = array(
	"ID" => $arParams["ELEMENT_ID"],
	"IBLOCK_ACTIVE" => "Y",
	"IBLOCK_ID" => $arParams["IBLOCK_ID"],
	"ACTIVE_DATE" => "Y",
	"CHECK_PERMISSIONS" => "Y"
);

//EXECUTE
$rsElement = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
if (!$obElement = $rsElement->GetNextElement())
{
	ShowError(GetMessage("IBLOCK_CSC_ELEMENT_NOT_FOUND"));
	@define("ERROR_404", "Y");
	CHTTP::SetStatus("404 Not Found");
	return false;
}

$arResult["ELEMENT"] = $obElement->GetFields();
if ($arResult["ELEMENT"]["ACTIVE"] != "Y")
	return false;

$arResult["ELEMENT"]["PROPERTIES"] = array();
foreach ($arResult["ELEMENT"] as $key => $val)
{
	if ((substr($key, 0, 9) == "PROPERTY_" && substr($key, -6, 6) == "_VALUE"))
		$arResult["ELEMENT"]["PROPERTIES"][substr($key, 9, intVal(strLen($key)-15))] = array("VALUE" => $val);
}

if(isset($arParams["BLOG_URL"]) && trim($arParams["BLOG_URL"]) != "")
	$arResult["BLOG_URL"] = $arParams["BLOG_URL"];
else
	$arResult["BLOG_URL"] = "catalog_comments";

$SITE_ID = (defined("SITE_ID") && strLen(SITE_ID) > 0 ? SITE_ID : "s1");

$arFields = array(
	"SITE_ID" => $SITE_ID,
	"NAME" => GetMessage("IBLOCK_CSC_BLOG_GROUP_NAME")
);

$dbBlogGroup = CBlogGroup::GetList(array(), $arFields, false, false, array("ID"));

if($arBlogGroup = $dbBlogGroup->Fetch())
	$blogGroupID = $arBlogGroup["ID"];
else
	$blogGroupID = CBlogGroup::Add($arFields);
$blogGroupID = intval($blogGroupID);

if($blogGroupID > 0)
{
	$dbBlog = CBlog::GetList(array(), array("URL" => $arResult["BLOG_URL"]), false, false, array("ID"));

	if($arBlog = $dbBlog->Fetch())
	{
		$blogId = $arBlog["ID"];
	}
	else
	{
		$arFields = array(
			"NAME" => GetMessage("IBLOCK_CSC_BLOG_NAME"),
			"DESCRIPTION" => GetMessage("IBLOCK_CSC_BLOG_DESCRIPTION"),
			"GROUP_ID" => $blogGroupID,
			"ENABLE_COMMENTS" => 'Y',
			"ENABLE_IMG_VERIF" => 'Y',
			"EMAIL_NOTIFY" => isset($arParams["EMAIL_NOTIFY"]) && $arParams["EMAIL_NOTIFY"] == 'Y' ? 'Y' : 'N',
			"URL" => $arResult["BLOG_URL"],
			"ACTIVE" => "Y",
			"OWNER_ID" => 1,
			"AUTO_GROUPS" => "N"
		);

		$blogId = CBlog::Add($arFields);

		if(IntVal($blogId) > 0)
		{
			CBlog::SetBlogPerms(
				$blogId,
				array(
					"1" => BLOG_PERMS_WRITE,
					"2" => BLOG_PERMS_WRITE
				),
				BLOG_PERMS_COMMENT
			);

		}
		else
		{
			if ($ex = $APPLICATION->GetException())
				$arResult["ERRORS"][] = $ex->GetString();
			else
				$arResult["ERRORS"][] = GetMessage("IBLOCK_CSC_BLOG_CREATE_ERROR");

		}
	}
}
else
{
	if ($ex = $APPLICATION->GetException())
		$arResult["ERRORS"][] = $ex->GetString();
	else
		$arResult["ERRORS"][] = GetMessage("IBLOCK_CSC_BLOG_GROUP_CREATE_ERROR");
}

if(empty($arResult["ERRORS"]))
{
	$obProperty = false;
	$iCommentID = 0;

	/************** BLOG *****************************************************/

	$obProperty = new CIBlockProperty;
	if (is_set($arResult["ELEMENT"]["PROPERTIES"], "BLOG_POST_ID"))
	{
		$iCommentID = intVal($arResult["ELEMENT"]["PROPERTIES"]["BLOG_POST_ID"]["VALUE"]);
	}
	else
	{
		$res = $obProperty->Add(array(
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"ACTIVE" => "Y",
				"PROPERTY_TYPE" => "N",
				"MULTIPLE" => "N",
				"NAME" => (strLen(GetMessage("IBLOCK_CSC_BLOG_POST_ID")) <= 0 ? "IBLOCK_CSC_BLOG_POST_ID" : GetMessage("IBLOCK_CSC_BLOG_POST_ID")),
				"CODE" => "BLOG_POST_ID"
			)
		);
	}

	if (!is_set($arResult["ELEMENT"], "PROPERTY_BLOG_COMMENTS_CNT_VALUE"))
	{
		$res = $obProperty->Add(array(
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"ACTIVE" => "Y",
				"PROPERTY_TYPE" => "N",
				"MULTIPLE" => "N",
				"NAME" => (strLen(GetMessage("IBLOCK_CSC_BLOG_COMMENTS_CNT")) <= 0 ? "IBLOCK_CSC_BLOG_COMMENTS_CNT" : GetMessage("IBLOCK_CSC_BLOG_COMMENTS_CNT")),
				"CODE" => "BLOG_COMMENTS_CNT"
			)
		);
	}

	if ($iCommentID > 0)
	{
		$arPost = CBlogPost::GetByID($iCommentID);
		if (!$arPost)
			$iCommentID = 0;
		elseif (intVal($arPost["NUM_COMMENTS"]) > 0 && $arPost["NUM_COMMENTS"] != $arResult["ELEMENT"]["PROPERTIES"]["BLOG_COMMENTS_CNT"]["VALUE"])
			CIBlockElement::SetPropertyValues($arParams["ELEMENT_ID"], $arParams["IBLOCK_ID"], intVal($arPost["NUM_COMMENTS"]), "BLOG_COMMENTS_CNT");
	}

	if (!$iCommentID && isset($_REQUEST["parentId"]))
	{
		$arCategory = array();
		$arBlog = CBlog::GetByUrl($arResult["BLOG_URL"]);
		if (!empty($arResult["ELEMENT"]["TAGS"]))
		{
			$arCategoryVal = explode(",", $arResult["ELEMENT"]["TAGS"]);
			foreach($arCategoryVal as $k => $v)
			{
				if ($id = CBlogCategory::Add(array("BLOG_ID"=>$arBlog["ID"],"NAME"=>$v)))
					$arCategory[] = $id;
			}
		}

		$ownerID = 1;
		if (!empty($arResult["ELEMENT"]["CREATED_BY"]))
		{
			$userSort = 'ID';
			$userOrder = 'ASC';
			$rsUsers = CUser::GetList($userSort, $userOrder, array('ID_EQUAL_EXACT' => intval($arResult["ELEMENT"]["CREATED_BY"])), array("FIELDS"=>array("ID")));
			if ($owner = $rsUsers->Fetch())
			{
				$ownerID = $owner['ID'];
			}
		}

		$arFields=array(
			"TITLE"			=> $arResult["ELEMENT"]["NAME"],
			"DETAIL_TEXT" =>
				"[URL=http://".$_SERVER['HTTP_HOST'].$arResult["ELEMENT"]["~DETAIL_PAGE_URL"]."]".$arResult["ELEMENT"]["NAME"]."[/URL]\n".
				(!empty($arResult["ELEMENT"]["TAGS"]) ? $arResult["ELEMENT"]["TAGS"]."\n" : "").
				$arResult["ELEMENT"]["~DETAIL_TEXT"]."\n",
			"CATEGORY_ID"		=> implode(",", $arCategory),
			"PUBLISH_STATUS"	=> "P",
			"PERMS_POST"	=> array(),
			"PERMS_COMMENT"	=> array(),
			"=DATE_CREATE"	=> $DB->GetNowFunction(),
			"=DATE_PUBLISH"	=> $DB->GetNowFunction(),
			"AUTHOR_ID"	=>	$ownerID,
			"BLOG_ID"	=> $arBlog["ID"],
			"ENABLE_TRACKBACK" => "N");

		$newID = CBlogPost::Add($arFields);
		if ($newID > 0)
		{
			foreach($arCategory as $key)
				CBlogPostCategory::Add(Array("BLOG_ID" => $arBlog["ID"], "POST_ID" => $newID, "CATEGORY_ID"=>$key));

			$iCommentID = $newID;
			CIBlockElement::SetPropertyValues($arResult["ELEMENT"]["ID"], $arParams["IBLOCK_ID"], $iCommentID, "BLOG_POST_ID");
		}
	}

	$arResult["COMMENT_ID"] = $iCommentID;

	CJSCore::Init(array('window', 'ajax'));
}

$protocol = (CMain::IsHTTPS()) ? "https://" : "http://";

if(isset($arParams["URL_TO_COMMENT"]) && strlen($arParams["URL_TO_COMMENT"]) > 0)
	$arResult["URL_TO_COMMENT"] = $arParams["URL_TO_COMMENT"];
elseif(isset($arResult["ELEMENT"]["DETAIL_PAGE_URL"]))
	$arResult["URL_TO_COMMENT"] = $protocol.$_SERVER["HTTP_HOST"].$arResult["ELEMENT"]["DETAIL_PAGE_URL"];
else
	$arResult["URL_TO_COMMENT"] = $protocol.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

if(isset($arParams["COMMENTS_COUNT"]))
	$arParams["COMMENTS_COUNT"] = intval($arParams["COMMENTS_COUNT"]);

if(!isset($arParams["AJAX_POST"]) || trim($arParams["AJAX_POST"]) == "")
	$arParams["AJAX_POST"] = 'N';

if(
	$arParams["AJAX_POST"] == "Y"
	&&
	(
		(
			isset($_REQUEST["post"])
			&& isset($_REQUEST["comment"])
		)
		|| isset($_REQUEST["hide_comment_id"])
		|| isset($_REQUEST["delete_comment_id"])
	)
)
{
	$arResult["IS_AJAX"] = true;
}
else
	$arResult["IS_AJAX"] = false;

$arParams["WIDTH"] = intval($arParams["WIDTH"]);
if($arParams["WIDTH"] > 0)
	$arResult["WIDTH"] = $arParams["WIDTH"];

$this->IncludeComponentTemplate();
?>