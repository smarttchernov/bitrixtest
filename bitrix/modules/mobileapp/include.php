<?
CModule::AddAutoloadClasses(
	"mobileapp",
	array(
		"CMobile" => "classes/general/mobile.php",
		"CAdminMobileDetail" => "classes/general/interface.php",
		"CAdminMobileDetailTmpl" => "classes/general/interface.php",
		"CAdminMobileMenu" => "classes/general/interface.php",
		"CAdminMobileFilter" => "classes/general/filter.php",
		"CMobileLazyLoad" => "classes/general/interface.php",
		"CAdminMobileEdit" => "classes/general/interface.php",
		"CMobileAppPullSchema" => "classes/general/pull.php",
		"CAdminMobilePush" => "classes/general/push.php",
	)
);
?>
