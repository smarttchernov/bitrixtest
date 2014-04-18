<?
$MESS["SECURITY_SITE_CHECKER_EnvironmentTest_NAME"] = "Проверка настроек окружения";
$MESS["SECURITY_SITE_CHECKER_SESSION"] = "Директория хранения файлов сессий доступна для всех системных пользователей";
$MESS["SECURITY_SITE_CHECKER_SESSION_DETAIL"] = "Это может позволить читать/изменять сессионные данные, через скрипты других виртуальных серверов";
$MESS["SECURITY_SITE_CHECKER_SESSION_RECOMMENDATION"] = "Корректно настроить файловые права или сменить директорию хранения либо включить хранение сессий в БД: <a href=\"/bitrix/admin/security_session.php\">Защита сессий</a>";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION"] = "Предположительно в директории хранения сессий находятся сессии других проектов";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_DETAIL"] = "Это может позволить читать/изменять сессионные данные, через скрипты других виртуальных серверов";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_RECOMMENDATION"] = "Сменить директорию хранения либо включить хранение сессий в БД: <a href=\"/bitrix/admin/security_session.php\">Защита сессий</a>";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP"] = "PHP скрипты выполняются в директории хранения загружаемых файлов";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DETAIL"] = "Разработчики иногда забывают о правильной фильтрации имен файлов, если это случится злоумышленник сможет получить полный контроль над вашим проектом";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_RECOMMENDATION"] = "Корректно настроить веб-сервер";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE"] = "PHP скрипты с двойным расширением (eg php.lala) выполняются в директории хранения загружаемых файлов";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_DETAIL"] = "Разработчики иногда забывают о правильной фильтрации имен файлов, если это случится злоумышленник сможет получить полный контроль над вашим проектом";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_RECOMMENDATION"] = "Корректно настроить веб-сервер";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY"] = "Py скрипты выполняются в директории хранения загружаемых файлов";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_DETAIL"] = "Разработчики иногда забывают о правильной фильтрации имен файлов, если это случится злоумышленник сможет получить полный контроль над вашим проектом";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_RECOMMENDATION"] = "Корректно настроить веб-сервер";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS"] = ".htaccess файлы не должны обрабатываться Apache в директории хранения загружаемых файлов";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_DETAIL"] = "Разработчики иногда забывают о правильной фильтрации имен файлов, если это случится злоумышленник сможет получить полный контроль над вашим проектом";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_RECOMMENDATION"] = "Корректно настроить веб-сервер";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION"] = "Apache Content Negotiation разрешен в директории хранения загружаемых файлов";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION_DETAIL"] = "Apache Content Negotiation не рекомендован для использования, т.к. может служить источником XSS нападения";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION_RECOMMENDATION"] = "Корректно настроить веб-сервер";
?>