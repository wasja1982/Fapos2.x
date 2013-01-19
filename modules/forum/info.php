<?php


$menuInfo = array(
    'url' => 'settings.php?m=forum',
    'ankor' => 'Форум',
	'sub' => array(
        'settings.php?m=forum' => 'Настройки',
        'design.php?m=forum' => 'Дизайн',
        'forum_cat.php' => 'Управление форумами',
        'forum_repair.php' => 'Пересчет сообщений',
	),
);




$settingsInfo = array(
	'title' => array(
		'type' => 'text',
		'title' => 'Заголовок',
		'description' => 'Заголовок, который подставится в блок <title></title>',
	),
	'description' => array(
		'type' => 'text',
		'title' => 'Описание',
		'description' => 'То, что подставится в мета тег description',
	),
	'not_reg_user' => array(
		'type' => 'text',
		'title' => 'Псевдоним гостя',
		'description' => '(Под этим именем будет показано сообщение (пост) <br>
 не зарегистрированного пользователя)',
	),
	
	
	'Ограничения' => 'Ограничения',
	'max_file_size' => array(
		'type' => 'text',
		'title' => 'Максимальный размер вложения',
		'description' => '',
		'help' => 'Байт',
	),
	'max_post_lenght' => array(
		'type' => 'text',
		'title' => 'Максимальная длина сообщения',
		'description' => '',
		'help' => 'Символов',
	),
	'posts_per_page' => array(
		'type' => 'text',
		'title' => 'Постов на странице',
		'description' => '',
		'help' => '',
	),
	'themes_per_page' => array(
		'type' => 'text',
		'title' => 'Тем на странице',
		'description' => '',
		'help' => '',
	),

	'Изображения' => 'Изображения',
	'use_preview' => array(
		'type' => 'checkbox',
		'title' => 'Использовать эскизы инображений',
		'description' => 'Возможность автоматического создания эскизов для больших изображений.',
		'value' => '1',
		'checked' => '1',
	),
	'img_size_x' => array(
		'type' => 'text',
		'title' => 'Ширина эскиза',
		'description' => 'Максимально допустимый размер эскиза по горизонтали.',
		'help' => 'px',
	),
	'img_size_y' => array(
		'type' => 'text',
		'title' => 'Высота эскиза',
		'description' => 'Максимально допустимый размер эскиза по вертикали.',
		'help' => 'px',
	),

	
	'Прочее' => 'Прочее',
	'active' => array(
		'type' => 'checkbox',
		'title' => 'Статус',
		'description' => '(Активирован/Деактивирован)',
		'value' => '1',
		'checked' => '1',
	),
);
