<!DOCTYPE HTML>
<html>
<head>
<? $APPLICATION->ShowHead() ?>
<title><? $APPLICATION->ShowTitle() ?></title>
<!--[if lt IE 9]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>

<? $APPLICATION->ShowPanel(); ?>

<div class="container">


<header>
     <a class="logotype" href="/">
         <img src="<?= SITE_TEMPLATE_PATH ?>/images/logo.png" width="222" height="33" alt="описание" />
      </a>
     
     <nav>
         <?$APPLICATION->IncludeComponent("bitrix:menu", "top_menu", Array(
	"ROOT_MENU_TYPE" => "top",	// Тип меню для первого уровня
	"MENU_CACHE_TYPE" => "N",	// Тип кеширования
	"MENU_CACHE_TIME" => "3600",	// Время кеширования (сек.)
	"MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
	"MENU_CACHE_GET_VARS" => array(	// Значимые переменные запроса
		0 => "",
	),
	"MAX_LEVEL" => "1",	// Уровень вложенности меню
	"CHILD_MENU_TYPE" => "",	// Тип меню для остальных уровней
	"USE_EXT" => "N",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
	"DELAY" => "N",	// Откладывать выполнение шаблона меню
	"ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
	),
	false
);?>
      </nav>
     
     <div class="contacts">
          г. Санкт-Петербург, ул. Невская<br/>
         дом 123, корпус 4, оф. 234-А<br/>
        <span>+7 (812) <b>234-43-43</b></span>
     </div>
</header>

<div class="it_asist"></div>

<table height="235" width="100%;" cellpadding="0" cellspacing="0" border="0">
    <tr><td width="77%">
    
    </td><td width="23%">
    
    </td></tr>
</table>

<div class="it_asist"></div>

<section>
     <aside class="left">
     Рыбные тексты также применяются для демонстрации различных видов
шрифта и в разработке макетов. Как правило их содержание бессмыслен
но. По причине своей функции текста-заполнителя для макетов нечитабе
льность рыбных текстов имеет особое значение, так как человеческое 
восприятие имеет особенность, распознавать определенные образцы и 
повторения. 
     </aside>
     
     <article>]
          