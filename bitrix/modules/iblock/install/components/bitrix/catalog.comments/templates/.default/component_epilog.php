<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if($arParams["BLOG_USE"] == "Y")
{
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/blog/templates/.default/style.css');
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/blog/templates/.default/themes/blue/style.css');
}

if($arParams["FB_USE"] == "Y")
{
	if(isset($arParams["FB_USER_ADMIN_ID"]) && strlen($arParams["FB_USER_ADMIN_ID"]) > 0)
		$APPLICATION->AddHeadString('<meta property="fb:admins" content="'.$arParams["FB_USER_ADMIN_ID"].'"/>');

	if(isset($arParams["FB_APP_ID"]) && strlen($arParams["FB_APP_ID"]) > 0)
		$APPLICATION->AddHeadString('<meta property="fb:app_id" content="'.$arParams["FB_APP_ID"].'"/>');
}

if($arParams["VK_USE"] == "Y")
	$APPLICATION->AddHeadString('<script src="http://userapi.com/js/api/openapi.js" type="text/javascript" charset="windows-1251"></script>');

?>
