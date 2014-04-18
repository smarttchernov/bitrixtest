<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template;
/**
 * Class Helper
 * Provides some helper functions.
 * @package Bitrix\Iblock\Template
 */
class Helper
{
	public static function splitTemplate($template)
	{
		if (preg_match("/\\/(l|t.?)+\$/", $template, $match))
		{
			return array(substr($template, 0, -strlen($match[0])), substr($match[0], 1));
		}
		else
		{
			return array($template, "");
		}
	}
	public static function splitModifiers($modifiers)
	{
		if (preg_match_all("/(l|t.?)/", $modifiers, $match))
			return $match[0];
		else
			return array();
	}
	public static function convertArrayToModifiers($template)
	{
		$TEMPLATE = $template["TEMPLATE"];
		$modifiers = "";
		if ($template["LOWER"] === "Y")
			$modifiers .= "l";
		if ($template["TRANSLIT"] === "Y")
		{
			$modifiers .= "t";
			if ($template["SPACE"] != "")
				$modifiers .= $template["SPACE"];
		}
		if ($modifiers != "")
			$modifiers = "/".$modifiers;
		return $TEMPLATE.$modifiers;
	}
	public static function convertModifiersToArray($template)
	{
		$TEMPLATE = $template["TEMPLATE"];
		$LOWER = "N";
		$TRANSLIT = "N";
		$SPACE = "";

		list($TEMPLATE, $modifiers) = self::splitTemplate($TEMPLATE);
		foreach(self::splitModifiers($modifiers) as $mod)
		{
			if ($mod == "l")
			{
				$LOWER = "Y";
			}
			else
			{
				$TRANSLIT = "Y";
				$SPACE = substr($mod, 1);
			}
		}

		$template["TEMPLATE"] = $TEMPLATE;
		$template["LOWER"] = $LOWER;
		$template["TRANSLIT"] = $TRANSLIT;
		$template["SPACE"] = $SPACE;

		return $template;
	}
	public static function makeFileName(\Bitrix\Iblock\InheritedProperty\BaseTemplate $ipropTemplates, $templateName, $arFields, $arFile)
	{
		if (preg_match("/^(.+)(\\.[a-zA-Z0-9]+)\$/", $arFile["name"], $fileName))
		{
			if (!isset($arFields["IPROPERTY_TEMPLATES"]) || $arFields["IPROPERTY_TEMPLATES"][$templateName] == "")
			{
				$templates = $ipropTemplates->findTemplates();
				$TEMPLATE = $templates[$templateName]["TEMPLATE"];
			}
			else
			{
				$TEMPLATE = $arFields["IPROPERTY_TEMPLATES"][$templateName];
			}

			if ($TEMPLATE != "")
			{
				list($template, $modifiers) = Helper::splitTemplate($TEMPLATE);
				if ($template != "")
				{
					$values = $ipropTemplates->getValuesEntity();
					$entity = $values->createTemplateEntity();
					$entity->setFields($arFields);
					return \Bitrix\Iblock\Template\Engine::process($entity, $TEMPLATE).$fileName[2];
				}
				elseif ($modifiers != "")
				{
					$simpleTemplate = new NodeRoot;
					$simpleTemplate->addChild(new NodeText($fileName[1]));
					$simpleTemplate->setModifiers($modifiers);
					$baseEntity = new Entity\Base(0);
					return $simpleTemplate->process($baseEntity).$fileName[2];
				}
			}
		}
		return $arFile["name"];
	}
}
