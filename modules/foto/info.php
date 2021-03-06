<?php

$menuInfo = array(
    'url' => 'settings.php?m=foto',
    'ankor' => 'Фото-каталог',
	'sub' => array(
        'settings.php?m=foto' => 'Настройки',
        'design.php?m=foto' => 'Дизайн',
        'category.php?mod=foto' => 'Управление категориями',
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
	
	
	'Ограничения' => 'Ограничения',
	'max_file_size' => array(
		'type' => 'text',
		'title' => 'Максимальный размер картинки',
		'description' => '',
		'help' => 'Байт',
	),
	'per_page' => array(
		'type' => 'text',
		'title' => 'Материалов на странице',
		'description' => '',
		'help' => '',
	),
	'description_lenght' => array(
		'type' => 'text',
		'title' => 'Максимальная длина описания',
		'description' => '',
		'help' => 'Символов',
	),
	
	
	'Поля обязательные для заполнения' => 'Поля обязательные для заполнения',
	'category_field' => array(
		'type' => 'checkbox',
		'title' => 'Категория',
		'attr' => array(
			'disabled' => 'disabled',
			'checked' => 'checked',
		),
	),
	'title_field' => array(
		'type' => 'checkbox',
		'title' => 'Заголовок',
		'attr' => array(
			'disabled' => 'disabled',
			'checked' => 'checked',
		),
	),
	'file_field' => array(
		'type' => 'checkbox',
		'title' => 'Файл',
		'attr' => array(
			'disabled' => 'disabled',
			'checked' => 'checked',
		),
	),
	'sub_description' => array(
		'type' => 'checkbox',
		'title' => 'Описание',
		'value' => 'description',
		'fields' => 'fields',
		'checked' => '1',
	),
	
	
	'Комментарии' => 'Комментарии',
	'comment_active' => array(
		'type' => 'checkbox',
		'title' => 'Разрешить использование комментариев',
		'description' => '',
        'value' => '1',
        'checked' => '1',
	),
	'comment_per_page' => array(
		'type' => 'text',
		'title' => 'Комментариев на странице',
		'description' => '',
		'help' => '',
	),
    'comment_lenght' => array(
        'type' => 'text',
        'title' => 'Максимальный размер',
		'description' => '',
		'help' => 'Символов',
    ),
	'comments_order' => array(
		'type' => 'checkbox',
		'title' => 'Новые сверху',
		'description' => '',
        'value' => '1',
        'checked' => '1',
	),
	
	
	'Прочее' => 'Прочее',
	'calc_count' => array(
		'type' => 'checkbox',
		'title' => 'Отображать количество материалов',
		'checked' => '1',
		'value' => '1',
		'description' => 'в списке категорий',
	),
	'active' => array(
		'type' => 'checkbox',
		'title' => 'Статус',
		'description' => '(Активирован/Деактивирован)',
		'value' => '1',
		'checked' => '1',
	),
);
