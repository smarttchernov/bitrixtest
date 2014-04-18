<?
$MESS["SECURITY_SITE_CHECKER_EnvironmentTest_NAME"] = "Environment check";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP"] = "PHP scripts are executed in the uploaded files directory.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_RECOMMENDATION"] = "Configure your web server correctly.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE"] = "PHP scripts with the double extension (e.g. php.lala) are executed in the uploaded files directory.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_RECOMMENDATION"] = "Configure your web server correctly.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY"] = "Python scripts are executed in the uploaded files directory.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_RECOMMENDATION"] = "Configure your web server correctly.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS"] = "Apache must not process the .htaccess files in the uploaded files directory";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_RECOMMENDATION"] = "Configure your web server correctly.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION"] = "Apache Content Negotiation is enabled in file upload directory.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION_DETAIL"] = "Apache Content Negotiation is not recommended because it may incur XSS attacks.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION_RECOMMENDATION"] = "Configure your web server correctly.";
$MESS["SECURITY_SITE_CHECKER_SESSION"] = "Session storage directory is available to all system users.";
$MESS["SECURITY_SITE_CHECKER_SESSION_DETAIL"] = "This may help an attacker read and write session data using scripts on other virtual servers.";
$MESS["SECURITY_SITE_CHECKER_SESSION_RECOMMENDATION"] = "Configure access permissions correctly, or change the directory. Another option is to store sessions in database: <a href=\"/bitrix/admin/security_session.php\">Session protection</a>.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION"] = "The session storage directory may contain sessions of different projects.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_DETAIL"] = "This may help an attacker read and write session data using scripts on other virtual servers.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_RECOMMENDATION"] = "Change the directory or store sessions in database: <a href=\"/bitrix/admin/security_session.php\">Session protection</a>.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DETAIL"] = "Sometimes developers don't pay enough attention to proper file name filters. An attacker may exploit this vulnerability to take full control of your  project.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_DETAIL"] = "Sometimes developers don't pay enough attention to proper file name filters. An attacker may exploit this vulnerability to take full control of your  project.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_DETAIL"] = "Sometimes developers don't pay enough attention to proper file name filters. An attacker may exploit this vulnerability to take full control of your  project.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_DETAIL"] = "Sometimes developers don't pay enough attention to proper file name filters. An attacker may exploit this vulnerability to take full control of your  project.";
?>