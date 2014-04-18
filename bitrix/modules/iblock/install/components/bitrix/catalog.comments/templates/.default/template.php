<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

if(!empty($arResult["ERRORS"]))
{
	ShowError(implode("<br>", $arResult["ERRORS"]));
	return;
}

$arData = array();

/* BLOG COMMENTS */
if($arParams["BLOG_USE"] == "Y")
{
	if(!isset($_SESSION["IBLOCK_CATALOG_COMMENTS_PARAMS_".$arParams["IBLOCK_ID"]."_".$arParams["ELEMENT_ID"]]))
		$_SESSION["IBLOCK_CATALOG_COMMENTS_PARAMS_".$arParams["IBLOCK_ID"]."_".$arParams["ELEMENT_ID"]] = $arParams;

	if(isset($arParams["FROM_AJAX"]) && $arParams["FROM_AJAX"] == "Y")
	{
		$arBlogCommentParams = array(
			"SEO_USER" => "N",
			"ID" => $arResult["COMMENT_ID"],
			"BLOG_URL" => $arResult["BLOG_URL"],
			"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
			"COMMENTS_COUNT" => $arParams["COMMENTS_COUNT"],
			"DATE_TIME_FORMAT" => $DB->DateFormatToPhp(FORMAT_DATETIME),
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"AJAX_POST" => $arParams["AJAX_POST"],
			"AJAX_MODE" => "Y",
			"SIMPLE_COMMENT" => "Y",
			"SHOW_SPAM" => $arParams["SHOW_SPAM"],
			"NOT_USE_COMMENT_TITLE" => "Y",
			"SHOW_RATING" => $arParams["SHOW_RATING"],
			"RATING_TYPE" => $arParams["RATING_TYPE"],
			"PATH_TO_POST" => $arResult["URL_TO_COMMENT"],
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"NO_URL_IN_COMMENTS" => "L"
		);

		$APPLICATION->IncludeComponent(
			"bitrix:blog.post.comment",
			"adapt",
			$arBlogCommentParams,
			$component,
			array("HIDE_ICONS" => "Y")
		);

		return;
	}

	$arData["BLOG"] =  array(
			"NAME" => isset($arParams["BLOG_TITLE"]) && trim($arParams["BLOG_TITLE"]) != "" ? $arParams["BLOG_TITLE"] : GetMessage("IBLOCK_CSC_TAB_COMMENTS"),
			"ACTIVE" => "Y",
			"CONTENT" => '<div id="bx-cat-soc-comments-blg">'.GetMessage("IBLOCK_CSC_COMMENTS_LOADING").'</div>'
	);

	?>
	<script type="text/javascript">
		BX.ready( function(){
			JCCatalogSocnetsComments.ajaxUrl = "<?=$templateFolder."/ajax.php?IBLOCK_ID=".$arParams["IBLOCK_ID"]."&ELEMENT_ID=".$arParams["ELEMENT_ID"]?>";
			BX.addCustomEvent("onIblockCatalogCommentSubmit", function(){ JCCatalogSocnetsComments.hacksForCommentsWindow(true); });
			window["iblockCatalogCommentsIntervalId"] = setInterval( function(){
					if(typeof JCCatalogSocnetsComments.getBlogAjaxHtml == 'function')
					{
						JCCatalogSocnetsComments.getBlogAjaxHtml();
						clearInterval(window["iblockCatalogCommentsIntervalId"]);
					}
				},
				200
			);
		});
	</script>
	<?

}

/* FACEBOOK */
if($arParams["FB_USE"] == "Y")
{

	$arData["FB"] = array(
		"NAME" => isset($arParams["FB_TITLE"]) && trim($arParams["FB_TITLE"]) != "" ? $arParams["FB_TITLE"] : "Facebook",
		"CONTENT" => '
			<div id="fb-root"></div>
			<script type="text/javascript">
				(function(d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s); js.id = id;
				js.src = "//connect.facebook.net/'.(strtolower(LANGUAGE_ID)."_".strtoupper(LANGUAGE_ID)).'/all.js#xfbml=1";
				fjs.parentNode.insertBefore(js, fjs);
				}(document, "script", "facebook-jssdk"));

				BX.ready( function(){

					setTimeout(function(){ JCCatalogSocnetsComments.onFBResize(); }, 2500 );

					BX.addCustomEvent("onAfterBXCatTabsSetActive_soc_comments", function(params) {
						if(params.activeTab || params.activeTab == "FB")
						{
							FB.XFBML.parse(BX("bx-cat-soc-comments-fb"));
							JCCatalogSocnetsComments.onFBResize();
						}
					});

					window.onresize = function(){
						JCCatalogSocnetsComments.onFBResize();
					};
				});

			</script>

			<div id="bx-cat-soc-comments-fb"><div'.
			' class="fb-comments"'.
			' data-href="'.$arResult["URL_TO_COMMENT"].'"'.
			(isset($arParams["FB_COLORSCHEME"]) ? ' data-colorscheme="'.$arParams["FB_COLORSCHEME"].'"' : '').
			(isset($arParams["COMMENTS_COUNT"]) ? ' data-numposts="'.$arParams["COMMENTS_COUNT"].'"' : '').
			(isset($arParams["FB_ORDER_BY"]) ? ' data-order-by="'.$arParams["FB_ORDER_BY"].'"' : '').
			(isset($arResult["WIDTH"]) ? ' data-width="'.($arResult["WIDTH"] - 20).'"' : '').
			'></div></div>'.PHP_EOL
	);
}


/* VKONTAKTE*/
if($arParams["VK_USE"] == "Y")
{
	$arData["VK"] = array(
		"NAME" => isset($arParams["VK_TITLE"]) && trim($arParams["VK_TITLE"]) != "" ? $arParams["VK_TITLE"] : GetMessage("IBLOCK_CSC_TAB_VK"),
		"CONTENT" => '
			<div id="vk_comments"></div>

			<script type="text/javascript">
				BX.ready( function(){
						VK.init({
							apiId: "'.(isset($arParams["VK_API_ID"]) && strlen($arParams["VK_API_ID"]) > 0 ? $arParams["VK_API_ID"] : "API_ID").'",
							onlyWidgets: true
						});

						VK.Widgets.Comments(
							"vk_comments",
							{
								pageUrl: "'.$arResult["URL_TO_COMMENT"].'",'.
								(isset($arParams["COMMENTS_COUNT"]) ? "limit: ".$arParams["COMMENTS_COUNT"]."," : "").
								(isset($arResult["WIDTH"]) ? "width: ".($arResult["WIDTH"] - 20)."," : "").
								'attach: false
							}
						);
				});

			</script>'
	);
}

if(!empty($arData))
{
	$arTabsParams = array(
		"DATA" => $arData,
		"ID" => "soc_comments"
	);

	if(isset($arResult["WIDTH"]))
		$arTabsParams["WIDTH"] = $arResult["WIDTH"];

	$frame = $this->createFrame()->begin("");
	?><div id="soc_comments_div"><?

	$APPLICATION->IncludeComponent(
		"bitrix:catalog.tabs",
		".default",
		$arTabsParams,
		$component,
		array("HIDE_ICONS" => "Y")
	);
	?></div><?
	$frame->end();
}
else
	ShowError(GetMessage("IBLOCK_CSC_NO_DATA"));
?>