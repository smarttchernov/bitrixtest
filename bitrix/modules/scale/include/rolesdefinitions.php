<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$rolesDefinitions = array(

	//Special invisible role assigned to every server-node
	"mgmt" => array(
		"NAME" => Loc::getMessage("SCALE_RDEF_ROLE_MGMT"),
		"ACTIONS" => array(),
		"COLOR" => "orange",
		"HIDE_LOADBAR" => true,
		"HIDE_NOROLE" => true,
		"GRAPH_CATEGORIES" => array("NGINX")
	),

	"web" => array(
		"NAME" => "Apache",
		"ACTIONS" => array(),
		"COLOR" => "grey-blue",
		"LOADBAR_INFO" => "##HOSTNAME##-process_status_httpd-pcpu-g.rrd",
		"GRAPH_CATEGORIES" => array("APACHE")
	),

	"memcached" => array(
		"NAME" => "Memcached",
		"ACTIONS" => array(),
		"COLOR" => "sky-blue",
		"ROLE_ACTIONS" => array(
			"norole" => array("MEMCACHED_ADD_ROLE"),
			"notype" => array("MEMCACHED_DEL_ROLE")
		),
		"MONITORING_CATEGORIES" => array("MEMCACHED")
	),

	"sphinx" => array(
		"NAME" => "Sphinx",
		"ACTIONS" => array(),
		"COLOR" => "red"
	),

	"mysql" => array(
		"NAME" => "MySQL",
		"ACTIONS" => array(),
		"COLOR" => "green",
		"LOADBAR_INFO" => "##HOSTNAME##-process_status_mysqld-pcpu-g.rrd",
		"TYPES" => array(
			"master" => "M",
			"slave" => "S"
		),
		"ROLE_ACTIONS" => array(
			"master" => array(),
			"slave" => array("MYSQL_CHANGE_MASTER", "MYSQL_DEL_SLAVE"),
			"norole" => array("MYSQL_ADD_SLAVE", "MYSQL_ADD_SLAVE_FIRST"),
			"notype" => array()
		),
		"GRAPH_CATEGORIES" => array("MYSQL")
	),

	"SERVER" => array(
		"NAME" => "server",
		"ACTIONS" => array("CHANGE_PASSWD","DEL_SERVER", "REBOOT", "UPDATE_BVM", "CHANGE_PASSWD_BITRIX"),
		"COLOR" => "invisible",
		"MONITORING_CATEGORIES" => array("AVG_LOAD", "MEMORY", "HDD",  "NET", "HDDACT"),
		"GRAPH_CATEGORIES" => array("DISC", "NETWORK", "PROCESSES", "SYSTEM")
	)
);