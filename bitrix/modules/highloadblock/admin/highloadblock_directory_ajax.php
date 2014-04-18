<?
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define("PUBLIC_AJAX_MODE", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if(!CModule::IncludeModule("highloadblock"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SS_MODULE_NOT_INSTALLED'));
	die();
}

if(check_bitrix_sessid())
{
	CUtil::JSPostUnescape();
	function addTableXmlIDCell($intPropID, $arPropInfo)
	{
		return '<input type="text" onBlur="getDirectoryTableHead(this);" name="PROPERTY_DIRECTORY_VALUES['.$intPropID.'][UF_XML_ID]" id="PROPERTY_VALUES_XML_'.$intPropID.'" value="'.htmlspecialcharsbx($arPropInfo['UF_XML_ID']).'" size="15" maxlength="200" style="width:90%">';
	}

	function addTableIdCell($intPropID, $arPropInfo)
	{
		return '<input type="hidden" onBlur="getDirectoryTableHead(this);" name="PROPERTY_DIRECTORY_VALUES['.$intPropID.'][ID]" id="PROPERTY_VALUES_ID_'.$intPropID.'" value="'.htmlspecialcharsbx($arPropInfo['ID']).'" size="15" maxlength="200" style="width:90%">';
	}

	function addTableNameCell($intPropID, $arPropInfo)
	{
		return '<input type="text" name="PROPERTY_DIRECTORY_VALUES['.$intPropID.'][UF_NAME]" id="PROPERTY_VALUES_NAME_'.$intPropID.'" value="'.htmlspecialcharsbx($arPropInfo['UF_NAME']).'" size="35" maxlength="255" style="width:90%">';
	}

	function addTableLinkCell($intPropID, $arPropInfo)
	{
		return '<input type="text" name="PROPERTY_DIRECTORY_VALUES['.$intPropID.'][UF_LINK]" id="PROPERTY_VALUES_LINK_'.$intPropID.'" value="'.htmlspecialcharsbx($arPropInfo['UF_LINK']).'" size="35" style="width:90%">';
	}

	function addTableSortCell($intPropID, $arPropInfo)
	{
		$sort = (isset($arPropInfo['UF_SORT']) && intval($arPropInfo['UF_SORT']) > 0) ? intval($arPropInfo['UF_SORT']) : 100;
		return '<input type="text" name="PROPERTY_DIRECTORY_VALUES['.$intPropID.'][UF_SORT]" id="PROPERTY_VALUES_SORT_'.$intPropID.'" value="'.$sort.'" size="5" maxlength="11">';
	}

	function addTableFileCell($intPropID, $arPropInfo)
	{
		static $maxImageSize = null;
		if (null === $maxImageSize)
		{
			$maxImageSize = array(
				"W" => COption::GetOptionString("iblock", "list_image_size"),
				"H" => COption::GetOptionString("iblock", "list_image_size"),
			);
		}

		if (!array_key_exists('UF_FILE', $arPropInfo))
			return '';
		$arPropInfo["UF_FILE"] = intval($arPropInfo["UF_FILE"]);
		if(!CModule::IncludeModule('fileman'))
			return '';

		$strShowFile = '';
		if (0 < $arPropInfo["UF_FILE"])
		{
			$strShowFile = CFile::ShowFile(
				$arPropInfo["UF_FILE"],
				0,
				$maxImageSize['W'],
				$maxImageSize['H'],
				false
			);
			if ('' !== $strShowFile)
				$strShowFile .= '<br>';


		}

		return $strShowFile.CFile::InputFile(
			"PROPERTY_DIRECTORY_VALUES[$intPropID][FILE]",
			20,
			$arPropInfo["UF_FILE"],
			false, 0, "IMAGE", "", 0, "class=typeinput", "", true, false);
	}

	function addTableDefCell($intPropID, $arPropInfo)
	{
		return '<input type="'.('Y' == $arPropInfo['MULTIPLE'] ? 'checkbox' : 'radio').'" name="PROPERTY_VALUES_DEF'.('Y' == $arPropInfo['MULTIPLE'] ? '[]' : '').'" id="PROPERTY_VALUES_DEF_'.$arPropInfo['ID'].'" value="'.$arPropInfo['ID'].'" '.('1' == $arPropInfo['UF_DEF'] ? 'checked="checked"' : '').'>';
	}

	function addTableDescriptionCell($intPropID, $arPropInfo)
	{
		return '<input type="text" name="PROPERTY_DIRECTORY_VALUES['.$intPropID.'][UF_DESCRIPTION]" id="PROPERTY_VALUES_DESCRIPTION_'.$intPropID.'" value="'.htmlspecialcharsbx($arPropInfo['UF_DESCRIPTION']).'" style="width:100%">';
	}

	function addTableFullDescriptionCell($intPropID, $arPropInfo)
	{
		return '<input type="text" name="PROPERTY_DIRECTORY_VALUES['.$intPropID.'][UF_FULL_DESCRIPTION]" id="PROPERTY_VALUES_FULL_DESCRIPTION_'.$intPropID.'" value="'.htmlspecialcharsbx($arPropInfo['UF_FULL_DESCRIPTION']).'" style="width:90%">';
	}

	function addTableDelField($intPropID, $arPropInfo)
	{
		return '<input type="hidden" name="PROPERTY_DIRECTORY_VALUES['.$intPropID.'][UF_DELETE]" id="PROPERTY_VALUES_DELETE_'.$intPropID.'" value="N">';
	}

	function addTableRow($intPropID, $arPropInfo)
	{
		return'<tr id="hlbl_property_tr_'.$intPropID.'">
					<td style="vertical-align: top;"><div style="background: url(/bitrix/panel/main/images/bx-admin-sprite-small-1.png) no-repeat 6px -2446px; display: inline-block; cursor: pointer; height: 23px; margin:0px 0px 0 0; opacity: 0.7; vertical-align: middle;	width: 23px;" onClick="this.parentNode.parentNode.style.display = \'none\'; BX(\'PROPERTY_VALUES_DELETE_'.$intPropID.'\').value = \'Y\'"></div></td>
					<td style="vertical-align: top;">'.addTableNameCell($intPropID, $arPropInfo).addTableIdCell($intPropID, $arPropInfo).addTableDelField($intPropID, $arPropInfo).'</td>
					<td style="vertical-align: top;">'.addTableSortCell($intPropID, $arPropInfo).'</td>
					<td style="vertical-align: top; text-align:center">'.addTableXmlIDCell($intPropID, $arPropInfo).'</td>
					<td style="vertical-align: top;">'.addTableFileCell($intPropID, $arPropInfo).'</td>
					<td style="vertical-align: top; text-align:center">'.addTableLinkCell($intPropID, $arPropInfo).'</td>
					<td style="vertical-align: top; text-align:center">'.addTableDefCell($intPropID, $arPropInfo).'</td>
					<td style="vertical-align: top; text-align:center">'.addTableDescriptionCell($intPropID, $arPropInfo).'</td>
					<td style="vertical-align: top; text-align:center">'.addTableFullDescriptionCell($intPropID, $arPropInfo).'</td>
				</tr>
				';
	}
	$rowNumber = intval($_REQUEST['rowNumber']);
	$hlBlock = $_REQUEST['hlBlock'];
	$result = '';
	if(strlen($hlBlock) > 0)
	{
		$hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList(array("filter" => array("TABLE_NAME" => $hlBlock)))->fetch();
		$entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
		$entity_data_class = $entity->getDataClass();
		$rsData = $entity_data_class::getList(array());
		while($arData = $rsData->fetch())
		{
			$arResult[] = $arData;
		}
		if(is_array($arResult))
		{
			foreach($arResult as $key => $value)
			{
				$result .= addTableRow($rowNumber, $value);
				$rowNumber++;
			}
		}
	}

	if($result == '')
		$result = addTableRow($rowNumber, 0);
	$result .= '<tr style="display: none;"><td><input type="hidden" id="IB_MAX_ROWS_COUNT" value="'.$rowNumber.'"></td></tr>';
	echo CUtil::PhpToJSObject($result);
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>