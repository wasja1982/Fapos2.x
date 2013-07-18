<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Email:        drunyacoder@gmail.com         |
| @Site:         http://fapos.net              |
| @Version:      1.8.3                         |
| @Project:      CMS                           |
| @Package       CMS Fapos                     |
| @Subpackege    Stat Module                   |
| @Copyright     ©Andrey Brykin 2010-2013      |
| @Last mod      2013/07/16                    |
|----------------------------------------------|
|											   |
| any partial or not partial extension         |
| CMS Fapos,without the consent of the         |
| author, is illegal                           |
|----------------------------------------------|
| Любое распространение                        |
| CMS Fapos или ее частей,                     |
| без согласия автора, является не законным    |
\---------------------------------------------*/



/**
 *
 */
Class StatModule extends Module {

	/**
	 * @module_title  title of module
	 */
	public $module_title = 'Статьи';

	/**
	 * @template  layout for module
	 */
	public $template = 'stat';

	/**
	 * @module module indentifier
	 */
	public $module = 'stat';
	
	public $premoder_types = array('rejected', 'confirmed');

	/**
	 * default action ( show main page )
	 */
	public function index($tag = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));


		//формируем блок со списком  разделов
		$this->_getCatsTree();


		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}

		$where = array();
		// we need to know whether to show hidden
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false))
			$where['available'] = 1;
		if (!empty($tag)) {
			$tag = mysql_real_escape_string($tag);
			$where[] = "`tags` LIKE '%{$tag}%'";
		}

		if (!$this->ACL->turn(array('other', 'can_premoder'), false)) {
			$where['premoder'] = 'confirmed';
		}
		
		$total = $this->Model->getTotal(array('cond' => $where));
		$perPage = intval($this->Register['Config']->read('per_page', $this->module));
		if ($perPage < 1)
			$perPage = 10;
		list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL());
		$this->Register['pages'] = $pages;
		$this->Register['page'] = $page;
		$this->page_title .= ' (' . $page . ')';



		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs();
		$navi['pagination'] = $pages;

		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$firstOnPage = ($page - 1) * $perPage + 1;
		$lastOnPage = $firstOnPage + $recOnPage - 1;

		$navi['meta'] = __('Count all material') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
		$this->_globalize($navi);


		if ($total <= 0) {
			$html = __('Materials not found');
			return $this->_view($html);
		}


		$params = array(
			'page' => $page,
			'limit' => $perPage,
			'order' => getOrderParam(__CLASS__),
		);


		$this->Model->bindModel('attaches');
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);



		if (is_object($this->AddFields) && count($records) > 0) {
			$records = $this->AddFields->mergeRecords($records);
		}


		// create markers
		foreach ($records as $entity) {
			$this->Register['current_vars'] = $entity;
			$markers = array();


			$markers['moder_panel'] = $this->_getAdminBar($entity);
			$entry_url = get_url(entryUrl($entity, $this->module));
			$markers['entry_url'] = $entry_url;


			// Cut announce
			$announce = $this->Textarier->getAnnounce($entity->getMain(), $entry_url, 0, $this->Register['Config']->read('announce_lenght', $this->module), $entity);


			// replace image tags in text
			$attaches = $entity->getAttaches();
			if (!empty($attaches) && count($attaches) > 0) {
				foreach ($attaches as $attach) {
					if ($attach->getIs_image() == '1') {
						$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
					}
				}
			}

			$markers['announce'] = $announce;


			$markers['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
			$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());
			if ($entity->getTags())
				$entity->setTags(explode(',', $entity->getTags()));


			//set users_id that are on this page
			$this->setCacheTag(array(
				'user_id_' . $entity->getAuthor_id(),
				'record_id_' . $entity->getId(),
			));


			$entity->setAdd_markers($markers);
		}


		$source = $this->render('list.html', array('entities' => $records));


		//write int cache
		if ($this->cached)
			$this->Cache->write($source, $this->cacheKey, $this->cacheTags);


		return $this->_view($source);
	}

	/**
	 * Show materials in category. Category ID must be integer and not null.
	 */
	public function category($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Can not find category'), $this->getModuleURL());


		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$category = $sectionsModel->getById($id);
		if (!$category)
			return $this->showInfoMessage(__('Can not find category'), $this->getModuleURL());
		if (!$this->ACL->checkCategoryAccess($category->getNo_access()))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());


		$this->page_title = h($category->getTitle()) . ' - ' . $this->page_title;


		//формируем блок со списком  разделов
		$this->_getCatsTree($id);


		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}

		// we need to know whether to show hidden
		$childCats = $sectionsModel->getOneField('id', array('parent_id' => $id));
		$ids = '`category_id` = ' . $id;
		if ($childCats && is_array($childCats) && count($childCats) > 0)
			$ids .= ' OR `category_id` IN (' . implode(', ', array_unique($childCats)) . ')';
		$where = array($ids);
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) {
			$where['available'] = 1;
		}


		if (!$this->ACL->turn(array('other', 'can_premoder'), false)) {
			$where['premoder'] = 'confirmed';
		}
		
		$total = $this->Model->getTotal(array('cond' => $where));
		$perPage = intval($this->Register['Config']->read('per_page', $this->module));
		if ($perPage < 1)
			$perPage = 10;
		list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL('category/' . $id));
		$this->Register['pages'] = $pages;
		$this->Register['page'] = $page;
		$this->page_title .= ' (' . $page . ')';



		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs($id);
		$navi['pagination'] = $pages;

		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$firstOnPage = ($page - 1) * $perPage + 1;
		$lastOnPage = $firstOnPage + $recOnPage - 1;

		$navi['meta'] = __('Count material in cat') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
		$navi['category_name'] = h($category->getTitle());
		$this->_globalize($navi);


		if ($total <= 0) {
			$html = __('Materials not found');
			return $this->_view($html);
		}


		$params = array(
			'page' => $page,
			'limit' => $perPage,
			'order' => getOrderParam(__CLASS__),
		);


		$this->Model->bindModel('attaches');
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);


		if (is_object($this->AddFields) && count($records) > 0) {
			$records = $this->AddFields->mergeRecords($records);
		}


		// create markers
		foreach ($records as $entity) {
			$this->Register['current_vars'] = $entity;
			$markers = array();


			$markers['moder_panel'] = $this->_getAdminBar($entity);
			$entry_url = get_url(entryUrl($entity, $this->module));
			$markers['entry_url'] = $entry_url;


			$announce = $this->Textarier->getAnnounce($entity->getMain(), $entry_url, 0, $this->Register['Config']->read('announce_lenght', $this->module), $entity);


			// replace image tags in text
			$attaches = $entity->getAttaches();
			if (!empty($attaches) && count($attaches) > 0) {
				foreach ($attaches as $attach) {
					if ($attach->getIs_image() == '1') {
						$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
					}
				}
			}

			$markers['announce'] = $announce;


			$markers['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
			$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());
			if ($entity->getTags())
				$entity->setTags(explode(',', $entity->getTags()));


			//set users_id that are on this page
			$this->setCacheTag(array(
				'user_id_' . $entity->getAuthor_id(),
				'record_id_' . $entity->getId(),
			));


			$entity->setAdd_markers($markers);
		}


		$source = $this->render('list.html', array('entities' => $records));


		//write int cache
		if ($this->cached)
			$this->Cache->write($source, $this->cacheKey, $this->cacheTags);


		return $this->_view($source);
	}

	/**
	 * View entity. Entity ID must be integer and not null.
	 */
	public function view($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_materials'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$this->Model->bindModel('attaches');
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$entity = $this->Model->getById($id);


		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());
		if ($entity->getAvailable() == 0 && !$this->ACL->turn(array('other', 'can_see_hidden'), false))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		if (!$this->ACL->checkCategoryAccess($entity->getCategory()->getNo_access()))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());


		if (!$this->ACL->turn(array('other', 'can_premoder'), false) && in_array($entity->getPremoder(), array('rejected', 'nochecked'))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}
		
		// Some gemor with add fields
		if (is_object($this->AddFields)) {
			$entities = $this->AddFields->mergeRecords(array($entity));
			$entity = $entities[0];
		}


		$max_attaches = $this->Register['Config']->read('max_attaches', $this->module);
		if (empty($max_attaches) || !is_numeric($max_attaches))
			$max_attaches = 5;


		//category block
		$this->_getCatsTree($entity->getCategory_id());
		/* COMMENT BLOCK */
		if ($this->Register['Config']->read('comment_active', $this->module) == 1
				&& $this->ACL->turn(array($this->module, 'view_comments'), false)
				&& $entity->getCommented() == 1) {
			if ($this->ACL->turn(array($this->module, 'add_comments'), false))
				$this->comments_form = $this->_add_comment_form($id);
			$this->comments = $this->_get_comments($entity);
		}
		$this->Register['current_vars'] = $entity;


		//производим замену соответствующих участков в html шаблоне нужной информацией
		$this->page_title = h($entity->getTitle()) . ' - ' . $this->page_title;
		$tags = $entity->getTags();
		$description = $entity->getDescription();
		if (!empty($tags))
			$this->page_meta_keywords = h($tags);
		if (!empty($description))
			$this->page_meta_description = h($description);

		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['module_url'] = get_url($this->getModuleURL());
		$navi['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
		$navi['category_name'] = h($entity->getCategory()->getTitle());
		$navi['navigation'] = $this->_buildBreadCrumbs($entity->getCategory_id());
		$this->_globalize($navi);


		$markers = array();
		$markers['moder_panel'] = $this->_getAdminBar($entity);
		$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());


		$entry_url = get_url(entryUrl($entity, $this->module));
		$markers['entry_url'] = $entry_url;


		$announce = $this->Textarier->print_page($entity->getMain(), $entity->getAuthor() ? $entity->getAuthor()->getStatus() : 0, $entity->getTitle());


		// replace image tags in text
		$attaches = $entity->getAttaches();
		if (!empty($attaches) && count($attaches) > 0) {
			foreach ($attaches as $attach) {
				if ($attach->getIs_image() == '1') {
					$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
				}
			}
		}

		$markers['mainText'] = $announce;
		$markers['main_text'] = $markers['mainText'];
		$entity->setAdd_markers($markers);
		if ($entity->getTags())
			$entity->setTags(explode(',', $entity->getTags()));


		$this->setCacheTag(array(
			'user_id_' . $entity->getAuthor_id(),
			'record_id_' . $entity->getId(),
			(!empty($_SESSION['user']['status'])) ? 'user_group_' . $_SESSION['user']['status'] : 'user_group_' . 'guest',
		));


		$source = $this->render('material.html', array('entity' => $entity));


		$entity->setViews($entity->getViews() + 1);
		$entity->save();
		$this->DB->cleanSqlCache();

		return $this->_view($source);
	}

	/**
	 * Show materials by user. User ID must be integer and not null.
	 */
	public function user($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Can not find user'), $this->getModuleURL());


		$usersModel = $this->Register['ModManager']->getModelInstance('Users');
		$user = $usersModel->getById($id);
		if (!$user)
			return $this->showInfoMessage(__('Can not find user'), $this->getModuleURL());
		if (!$this->ACL->checkCategoryAccess($user->getNo_access()))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());


		$this->page_title = __('User materials') . ' "' . h($user->getName()) . '" - ' . $this->page_title;


		//формируем блок со списком  разделов
		$this->_getCatsTree();


		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}

		// we need to know whether to show hidden
		$where = array('author_id' => $id);
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) {
			$where['available'] = 1;
		}


		$total = $this->Model->getTotal(array('cond' => $where));
		$perPage = intval($this->Register['Config']->read('per_page', $this->module));
		if ($perPage < 1)
			$perPage = 10;
		list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL('user/' . $id));
		$this->Register['pages'] = $pages;
		$this->Register['page'] = $page;
		$this->page_title .= ' (' . $page . ')';



		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('User materials') . ' "' . h($user->getName()) . '"';
		$navi['pagination'] = $pages;

		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$firstOnPage = ($page - 1) * $perPage + 1;
		$lastOnPage = $firstOnPage + $recOnPage - 1;

		$navi['meta'] = __('Count all material') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
		$navi['category_name'] = __('User materials') . ' "' . h($user->getName()) . '"';
		$this->_globalize($navi);


		if ($total <= 0) {
			$html = __('Materials not found');
			return $this->_view($html);
		}


		$params = array(
			'page' => $page,
			'limit' => $perPage,
			'order' => getOrderParam(__CLASS__),
		);


		$this->Model->bindModel('attaches');
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);


		if (is_object($this->AddFields) && count($records) > 0) {
			$records = $this->AddFields->mergeRecords($records);
		}


		// create markers
		foreach ($records as $entity) {
			$this->Register['current_vars'] = $entity;
			$markers = array();


			$markers['moder_panel'] = $this->_getAdminBar($entity);
			$entry_url = get_url(entryUrl($entity, $this->module));
			$markers['entry_url'] = $entry_url;


			$announce = $this->Textarier->getAnnounce($entity->getMain(), $entry_url, 0, $this->Register['Config']->read('announce_lenght', $this->module), $entity);


			// replace image tags in text
			$attaches = $entity->getAttaches();
			if (!empty($attaches) && count($attaches) > 0) {
				foreach ($attaches as $attach) {
					if ($attach->getIs_image() == '1') {
						$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
					}
				}
			}

			$markers['announce'] = $announce;


			$markers['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
			$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());
			if ($entity->getTags())
				$entity->setTags(explode(',', $entity->getTags()));


			//set users_id that are on this page
			$this->setCacheTag(array(
				'user_id_' . $entity->getAuthor_id(),
				'record_id_' . $entity->getId(),
			));


			$entity->setAdd_markers($markers);
		}


		$source = $this->render('list.html', array('entities' => $records));


		//write int cache
		if ($this->cached)
			$this->Cache->write($source, $this->cacheKey, $this->cacheTags);


		return $this->_view($source);
	}

	/**
	 * return form to add
	 */
	public function add_form() {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));


		// categories block
		$this->_getCatsTree();


		// Additional fields
		$markers = array();
		if (is_object($this->AddFields)) {
			$_addFields = $this->AddFields->getInputs(array(), true, $this->module);
			foreach ($_addFields as $k => $field) {
				$markers[strtolower($k)] = $field;
			}
		}


		// Check for preview or errors
		$data = array('title' => null, 'mainText' => null, 'in_cat' => null, 'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null, 'sourse_site' => null, 'commented' => 1, 'available' => ($this->ACL->turn(array($this->module, 'need_check_materials'), false) ? 0 : 1));
		$data = array_merge($data, $markers);
		$data = Validate::getCurrentInputsValues($data);
		$data['main_text'] = $data['mainText'];


		$data['preview'] = $this->Parser->getPreview($data['mainText']);
		$data['errors'] = $this->Parser->getErrors();
		if (isset($_SESSION['viewMessage']))
			unset($_SESSION['viewMessage']);
		if (isset($_SESSION['FpsForm']))
			unset($_SESSION['FpsForm']);


		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$categories = $sectionsModel->getCollection();
		$data['cats_selector'] = $this->_buildSelector($categories, ((!empty($data['in_cat'])) ? $data['in_cat'] : false));


		//comments and hide
		$data['commented'] = (!empty($data['commented'])) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false))
			$data['commented'] .= ' disabled="disabled"';
		$data['available'] = (!empty($data['available'])) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false))
			$data['available'] .= ' disabled="disabled"';


		$data['action'] = get_url($this->getModuleURL('add/'));
		$data['max_attaches'] = $this->Register['Config']->read('max_attaches', $this->module);
		if (empty($data['max_attaches']) || !is_numeric($data['max_attaches']))
			$data['max_attaches'] = 5;


		// Navigation panel
		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs();
		$this->_globalize($navi);


		$source = $this->render('addform.html', array('context' => $data));
		return $this->_view($source);
	}

	/**
	 *
	 * Validate data and create a new record into
	 * Data Base. If an errors, redirect user to add form
	 * and show error message where speaks as not to admit
	 * errors in the future
	 *
	 */
	public function add() {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($_POST['mainText'])
				|| !isset($_POST['title'])
				|| !isset($_POST['cats_selector'])
				|| !is_numeric($_POST['cats_selector'])) {
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		}
		$error = '';


		// Check additional fields if an exists.
		// This must be doing after define $error variable.
		if (is_object($this->AddFields)) {
			$_addFields = $this->AddFields->checkFields();
			if (is_string($_addFields))
				$error .= $_addFields;
		}


		$fields = array('description', 'tags', 'sourse', 'sourse_email', 'sourse_site');
		$fields_settings = $this->Register['Config']->read('fields', $this->module);
		foreach ($fields as $field) {
			if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
				$error .= '<li>' . __('Empty field') . ' "' . $field . '"</li>' . "\n";
				$$field = null;
			} else {
				$$field = isset($_POST[$field]) ? h(trim($_POST[$field])) : '';
			}
		}

		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title = trim(mb_substr($_POST['title'], 0, 128));
		$main_text = trim($_POST['mainText']);
		$in_cat = intval($_POST['cats_selector']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		$available = (!empty($_POST['available'])) ? 1 : 0;
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false))
			$commented = 1;
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false))
			$available = ($this->ACL->turn(array($this->module, 'need_check_materials'), false) ? 0 : 1);

		// Если пользователь хочет посмотреть на сообщение перед отправкой
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat,
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null,
				'sourse_site' => null, 'commented' => $commented, 'available' => $available), $_POST);
			redirect($this->getModuleURL('add_form/'));
		}

		// Проверяем, заполнены ли обязательные поля
		$valobj = $this->Register['Validate'];  //validation data class
		if (empty($in_cat))
			$error .= '<li>' . __('Category not selected') . '</li>' . "\n";
		if (empty($title))
			$error .= '<li>' . __('Empty field "title"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($title, V_TITLE))
			$error .= '<li>' . __('Wrong chars in "title"') . '</li>' . "\n";
		$max_lenght = $this->Register['Config']->read('max_lenght', $this->module);
		if ($max_lenght <= 0)
			$max_lenght = 10000;
		if (empty($main_text))
			$error .= '<li>' . __('Empty field "material"') . '</li>' . "\n";
		elseif (mb_strlen($main_text) > $max_lenght)
			$error .= '<li>' . sprintf(__('Very big "material"'), $max_lenght) . '</li>' . "\n";
		if (!empty($tags) && !$valobj->cha_val($tags, V_TITLE))
			$error .= '<li>' . __('Wrong chars in "tags"') . '</li>' . "\n";
		if (!empty($sourse) && !$valobj->cha_val($sourse, V_TITLE))
			$error .= '<li>' . __('Wrong chars in "sourse"') . '</li>' . "\n";
		if (!empty($sourse_email) && !$valobj->cha_val($sourse_email, V_MAIL))
			$error .= '<li>' . __('Wrong chars in "email"') . '</li>' . "\n";
		if (!empty($sourse_site) && !$valobj->cha_val($sourse_site, V_URL))
			$error .= '<li>' . __('Wrong chars in "sourse site"') . '</li>' . "\n";


		// Check attaches size and format
		$max_attach = $this->Register['Config']->read('max_attaches', $this->module);
		if (empty($max_attach) || !is_numeric($max_attach))
			$max_attach = 5;
		$max_attach_size = $this->getMaxSize('max_attaches_size');
		if (empty($max_attach_size) || !is_numeric($max_attach_size))
			$max_attach_size = 1048576;
		for ($i = 1; $i <= $max_attach; $i++) {
			$attach_name = 'attach' . $i;
			if (!empty($_FILES[$attach_name]['name'])) {

				$ext = strrchr($_FILES[$attach_name]['name'], ".");

				if ($_FILES[$attach_name]['size'] > $max_attach_size) {
					$error .= '<li>' . sprintf(__('Very big file'), $i, round(($max_attach_size / 1024), 2)) . '</li>' . "\n";
				}
				if (!isImageFile($_FILES[$attach_name]['type'], $ext)) {
					$error .= '<li>' . __('Wrong file format') . '</li>' . "\n";
				}
			}
		}


		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$category = $sectionsModel->getById($in_cat);
		if (empty($category))
			$error .= '<li>' . __('Can not find category') . '</li>' . "\n";


		// Errors
		if (!empty($error)) {
			$_SESSION['FpsForm'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat,
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null,
				'sourse_site' => null, 'commented' => $commented, 'available' => $available), $_POST);
			$_SESSION['FpsForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'
					. "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			redirect($this->getModuleURL('add_form/'));
		}


		// Защита от того, чтобы один пользователь не добавил
		// 100 материалов за одну минуту
		if (isset($_SESSION['unix_last_post']) and ( time() - $_SESSION['unix_last_post'] < 10 )) {
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL());
		}


		// Auto tags generation
		if (empty($tags)) {
			$TagGen = new MetaTags;
			$tags = $TagGen->getTags($main_text);
			$tags = (!empty($tags) && is_array($tags)) ? implode(',', array_keys($tags)) : '';
		}


		//remove cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $this->module));
		$this->DB->cleanSqlCache();
		// Формируем SQL-запрос на добавление материала
		$data = array(
			'title' => $title,
			'main' => mb_substr($main_text, 0, $max_lenght),
			'date' => new Expr('NOW()'),
			'author_id' => $_SESSION['user']['id'],
			'category_id' => $in_cat,
			'description' => $description,
			'tags' => $tags,
			'sourse' => $sourse,
			'sourse_email' => $sourse_email,
			'sourse_site' => $sourse_site,
			'commented' => $commented,
			'available' => $available,
			'view_on_home' => $category->getView_on_home(),
			'premoder' 	   => 'confirmed',
		);
		if ($this->ACL->turn(array($this->module, 'materials_require_premoder'), false)) {
			$data['premoder'] = 'nochecked';
		}

		$className = $this->Register['ModManager']->getEntityName($this->module);
		$entity = new $className($data);
		if ($entity) {
			$last_id = $entity->save();

			// Get last insert ID and save additional fields if an exists and activated.
			// This must be doing only after save main(parent) material
			if (is_object($this->AddFields)) {
				$this->AddFields->save($last_id, $_addFields);
			}

			downloadAttaches($this->module, $last_id);


			// hook for plugins
			Plugins::intercept('new_entity', array(
				'entity' => $entity,
				'module' => $this->module,
			));
			
			//clean cache
			$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module));
			$this->DB->cleanSqlCache();
			if ($this->Log)
				$this->Log->write('adding ' . $this->module, $this->module . ' id(' . $last_id . ')');
			return $this->showInfoMessage(__('Material successfully added'), $this->getModuleURL('view/' . $last_id));
		} else {
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		}
	}

	/**
	 *
	 * Create form and fill his data from record which ID
	 * transfered into function. Show errors if an exists
	 * after unsuccessful attempt. Also can get data for filling
	 * from SESSION if user try preview message or create error.
	 *
	 * @param int $id material then to be edit
	 */
	public function edit_form($id = null) {
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$this->Model->bindModel('attaches');
		// $this->Model->bindModel('author');
		// $this->Model->bindModel('category');
		$entity = $this->Model->getById($id);

		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		if (is_object($this->AddFields) && count($entity) > 0) {
			$entities = $this->AddFields->mergeRecords(array($entity), true);
			$entity = $entities[0];
		}


		//turn access
		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false)
				&& (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		$this->Register['current_vars'] = $entity;

		//forming categories list
		$this->_getCatsTree($entity->getCategory_id());


		$data = array(
			'title' => '',
			'mainText' => $entity->getMain(),
			'in_cat' => $entity->getCategory_id(),
			'description' => '',
			'tags' => '',
			'sourse' => '',
			'sourse_email' => '',
			'sourse_site' => '',
			'commented' => '',
			'available' => '',
		);
		$markers = Validate::getCurrentInputsValues($entity, $data);
		$markers->setMain_text($markers->getMaintext());


		$markers->setPreview($this->Parser->getPreview($markers->getMain()));
		$markers->setErrors($this->Parser->getErrors());
		if (isset($_SESSION['viewMessage']))
			unset($_SESSION['viewMessage']);
		if (isset($_SESSION['FpsForm']))
			unset($_SESSION['FpsForm']);


		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$categories = $sectionsModel->getCollection();
		$selectedCatId = ($markers->getIn_cat()) ? $markers->getIn_cat() : $markers->getCategory_id();
		$cats_change = $this->_buildSelector($categories, $selectedCatId);


		//comments and hide
		$commented = ($markers->getCommented()) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false))
			$commented .= ' disabled="disabled"';
		$available = ($markers->getAvailable()) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false))
			$available .= ' disabled="disabled"';
		$markers->setAction(get_url($this->getModuleURL('update/' . $markers->getId())));
		$markers->setCommented($commented);
		$markers->setAvailable($available);


		$attaches = $markers->getAttaches();
		$attDelButtons = '';
		if (count($attaches)) {
			foreach ($attaches as $key => $attach) {
				$attDelButtons .= '<input type="checkbox" name="' . $attach->getAttach_number()
						. 'dattach"> ' . $attach->getAttach_number() . ' . (' . $attach->getFilename() . ')' . "<br />\n";
			}
		}


		$markers->setCats_selector($cats_change);
		$markers->setAttaches_delete($attDelButtons);
		$markers->setMax_attaches($this->Register['Config']->read('max_attaches', $this->module));


		// Navigation panel
		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs($entity->getCategory_id());
		$this->_globalize($navi);


		// setReferer();
		$source = $this->render('editform.html', array('context' => $markers));
		return $this->_view($source);
	}

	/**
	 *
	 * Validate data and update record into
	 * Data Base. If an errors, redirect user to add form
	 * and show error message where speaks as not to admit
	 * errors in the future
	 *
	 */
	public function update($id = null) {
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($id)
				|| !isset($_POST['title'])
				|| !isset($_POST['mainText'])
				|| !isset($_POST['cats_selector'])) {
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		}
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());
		$error = '';


		$entity = $this->Model->getById($id);
		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		//turn access
		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false)
				&& (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		// Check additional fields if an exists.
		// This must be doing after define $error variable.
		if (is_object($this->AddFields)) {
			$_addFields = $this->AddFields->checkFields();
			if (is_string($_addFields))
				$error .= $_addFields;
		}


		$valobj = $this->Register['Validate'];
		$fields = array('description', 'tags', 'sourse', 'sourse_email', 'sourse_site');
		$fields_settings = $this->Register['Config']->read('fields', $this->module);
		foreach ($fields as $field) {
			if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
				$error .= '<li>' . __('Empty field') . ' "' . $field . '"</li>' . "\n";
				$$field = null;
			} else {
				$$field = isset($_POST[$field]) ? h(trim($_POST[$field])) : '';
			}
		}

		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title = trim(mb_substr($_POST['title'], 0, 128));
		$main_text = trim($_POST['mainText']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		$available = (!empty($_POST['available'])) ? 1 : 0;
		$in_cat = intval($_POST['cats_selector']);
		if (empty($in_cat))
			$in_cat = $entity['category_id'];
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false))
			$commented = 1;
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false))
			$available = ($this->ACL->turn(array($this->module, 'need_check_materials'), false) ? 0 : 1);


		// Если пользователь хочет посмотреть на сообщение перед отправкой
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat,
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null,
				'sourse_site' => null, 'commented' => $commented, 'available' => $available), $_POST);
			redirect($this->getModuleURL('edit_form/' . $id));
		}


		// Проверяем, заполнены ли обязательные поля
		if (empty($title))
			$error .= '<li>' . __('Empty field "title"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($title, V_TITLE))
			$error .= '<li>' . __('Wrong chars in "title"') . '</li>' . "\n";
		$max_lenght = $this->Register['Config']->read('max_lenght', $this->module);
		if ($max_lenght <= 0)
			$max_lenght = 10000;
		if (empty($main_text))
			$error .= '<li>' . __('Empty field "material"') . '</li>' . "\n";
		elseif (mb_strlen($main_text) > $max_lenght)
			$error .= '<li>' . sprintf(__('Very big "material"'), $max_lenght) . '</li>' . "\n";
		if (!empty($tags) && !$valobj->cha_val($tags, V_TITLE))
			$error .= '<li>' . __('Wrong chars in "tags"') . '</li>' . "\n";
		if (!empty($sourse) && !$valobj->cha_val($sourse, V_TITLE))
			$error .= '<li>' . __('Wrong chars in "sourse"') . '</li>' . "\n";
		if (!empty($sourse_email) && !$valobj->cha_val($sourse_email, V_MAIL))
			$error .= '<li>' . __('Wrong chars in "email"') . '</li>' . "\n";
		if (!empty($sourse_site) && !$valobj->cha_val($sourse_site, V_URL))
			$error .= '<li>' . __('Wrong chars in "sourse site"') . '</li>' . "\n";



		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$category = $sectionsModel->getById($in_cat);
		if (!$category)
			$error .= '<li>' . __('Can not find category') . '</li>' . "\n";


		// Check attaches size and format
		$max_attach = $this->Register['Config']->read('max_attaches', $this->module);
		if (empty($max_attach) || !is_numeric($max_attach))
			$max_attach = 5;
		$max_attach_size = $this->getMaxSize('max_attaches_size');
		if (empty($max_attach_size) || !is_numeric($max_attach_size))
			$max_attach_size = 1048576;
		for ($i = 1; $i <= $max_attach; $i++) {
			// Delete attaches. If need
			$dattach = $i . 'dattach';
			$attach_name = 'attach' . $i;
			if (array_key_exists($dattach, $_POST) || !empty($_FILES[$attach_name]['name'])) {
				deleteAttach($this->module, $id, $i);
			}

			if (!empty($_FILES[$attach_name]['name'])) {

				$ext = strrchr($_FILES[$attach_name]['name'], ".");

				if ($_FILES[$attach_name]['size'] > $max_attach_size) {
					$error .= '<li>' . sprintf(__('Very big file'), $i, round(($max_attach_size / 1024), 2)) . '</li>' . "\n";
				}
				if (!isImageFile($_FILES[$attach_name]['type'], $ext)) {
					$error .= '<li>' . __('Wrong file format') . '</li>' . "\n";
					unset($_FILES[$attach_name]);
				}
			}
		}
		downloadAttaches($this->module, $id);


		// Errors
		if (!empty($error)) {
			$_SESSION['FpsForm'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat,
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null,
				'sourse_site' => null, 'commented' => $commented, 'available' => $available), $_POST);
			$_SESSION['FpsForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'
					. "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			redirect($this->getModuleURL('edit_form/' . $id));
		}


		//remove cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $id));
		$this->DB->cleanSqlCache();


		// Auto tags generation
		if (empty($tags)) {
			$TagGen = new MetaTags;
			$tags = $TagGen->getTags($main_text);
			$tags = (!empty($tags) && is_array($tags)) ? implode(',', array_keys($tags)) : '';
		}


		$data = array(
			'title' => $title,
			'main' => mb_substr($main_text, 0, $max_lenght),
			'category_id' => $in_cat,
			'description' => $description,
			'tags' => $tags,
			'sourse' => $sourse,
			'sourse_email' => $sourse_email,
			'sourse_site' => $sourse_site,
			'commented' => $commented,
			'available' => $available,
		);
		$entity->set($data);
		$entity->save();

		// Save additional fields if they is active
		if (is_object($this->AddFields)) {
			$this->AddFields->save($id, $_addFields);
		}


		if ($this->Log)
			$this->Log->write('editing ' . $this->module, $this->module . ' id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view/' . $id) /*getReferer()*/);
	}

	/**
	 * Check user access and if all right
	 * delete record with geting ID.
	 *
	 * @param int $id
	 */
	public function delete($id = null) {
		$this->cached = false;
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$entity = $this->Model->getById($id);
		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		//turn access
		if (!$this->ACL->turn(array($this->module, 'delete_materials'), false)
				&& (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		//remove cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $id));
		$this->DB->cleanSqlCache();

		$entity->delete();

		$user_id = (!empty($_SESSION['user']['id'])) ? intval($_SESSION['user']['id']) : 0;
		if ($this->Log)
			$this->Log->write('delete ' . $this->module, $this->module . ' id(' . $id . ') user id(' . $user_id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}

	/**
	 * add comment
	 *
	 * @id (int)    entity ID
	 * @return      info message
	 */
	public function add_comment($id = null) {
		include_once(ROOT . '/sys/inc/includes/add_comment.php');
	}

	/**
	 * add comment form
	 *
	 * @id (int)    entity ID
	 * @return      html form
	 */
	private function _add_comment_form($id = null) {
		include_once(ROOT . '/sys/inc/includes/_add_comment_form.php');
		return $html;
	}

	/**
	 * edit comment form
	 *
	 * @id (int)    comment ID
	 * @return      html form
	 */
	public function edit_comment_form($id = null) {
		include_once(ROOT . '/sys/inc/includes/edit_comment_form.php');
	}

	/**
	 * update comment
	 *
	 * @id (int)    comment ID
	 * @return      info message
	 */
	public function update_comment($id = null) {
		include_once(ROOT . '/sys/inc/includes/update_comment.php');
	}

	/**
	 * get comments
	 *
	 * @id (int)    entity ID
	 * @return      html comments list
	 */
	private function _get_comments($entity = null) {
		include_once(ROOT . '/sys/inc/includes/_get_comments.php');
		return $html;
	}

	/**
	 * delete comment
	 *
	 * @id (int)    comment ID
	 * @return      info message
	 */
	public function delete_comment($id = null) {
		include_once(ROOT . '/sys/inc/includes/delete_comment.php');
	}

	/**
	 * @param int $id - record ID
	 *
	 * update date by record also up record in recods list
	 */
	public function upper($id) {
		//turn access
		$this->ACL->turn(array($this->module, 'up_materials'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$entity = $this->Model->getById($id);
		if ($entity) {
			$entity->setDate(date("Y-m-d H:i:s"));
			$entity->save();
			return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
		}
		return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
	}

	/**
	 * @param int $id - record ID
	 *
	 * allow record be on home page
	 */
	public function on_home($id) {
		//turn access
		$this->ACL->turn(array($this->module, 'on_home'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$entity = $this->Model->getById($id);
		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());

		$entity->setView_on_home('1');
		$entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}

	/**
	 * @param int $id - record ID
	 *
	 * denied record be on home page
	 */
	public function off_home($id) {
		//turn access
		$this->ACL->turn(array($this->module, 'on_home'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$entity = $this->Model->getById($id);
		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());

		$entity->setView_on_home('0');
		$entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}

	/**
	 * @param int $id - record ID
	 *
	 * fix or unfix record on top on home page
	 */
	public function fix_on_top($id) {
		$this->ACL->turn(array($this->module, 'on_home'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());

		$entity = $this->Model->getById($id);
		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());

		$curr_state = $entity->getOn_home_top();
		$dest = ($curr_state) ? '0' : '1';
		$entity->setOn_home_top($dest);
		$entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}

	
	/**
	* @param int $id - record ID
	*
	* fix or unfix record on top on home page
	*/
	public function premoder($id, $type)
    {
		$this->ACL->turn(array('other', 'can_premoder'));
		$id = (int)$id;
		if ($id < 1) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		
		if (!in_array((string)$type, $this->premoder_types)) 
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());

		$target = $this->Model->getById($id);
		if (!$target) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		
		$target->setPremoder((string)$type);
		$target->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}
	
	
	/**
	 * @param array $record - assoc record array
	 * @return string - admin buttons
	 *
	 * create and return admin bar
	 */
	protected function _getAdminBar($record) {
		$moder_panel = '';
		$id = $record->getId();
		$uid = $record->getAuthor_id();
		if (!$uid)
			$uid = 0;

		if ($this->ACL->turn(array('other', 'can_premoder'), false) && 'nochecked' == $record->getPremoder()) {
			$moder_panel .= get_link('', $this->getModuleURL('premoder/' . $id . '/confirmed'),
				array(
					'class' => 'fps-premoder-confirm', 
					'title' => 'Confirm', 
					'onClick' => "return confirm('" . __('Are you sure') . "')",
				)) . '&nbsp;';
			$moder_panel .= get_link('', $this->getModuleURL('premoder/' . $id . '/rejected'),
				array(
					'class' => 'fps-premoder-reject', 
					'title' => 'Reject', 
					'onClick' => "return confirm('" . __('Are you sure') . "')",
				)) . '&nbsp;';
		} else if ($this->ACL->turn(array('other', 'can_premoder'), false) && 'rejected' == $record->getPremoder()) {
			$moder_panel .= get_link('', $this->getModuleURL('premoder/' . $id . '/confirmed'),
				array(
					'class' => 'fps-premoder-confirm', 
					'title' => 'Confirm', 
					'onClick' => "return confirm('" . __('Are you sure') . "')",
				)) . '&nbsp;';
		}
		
		if ($this->ACL->turn(array($this->module, 'edit_materials'), false)
				|| (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false))) {
			$moder_panel .= get_link('', $this->getModuleURL('edit_form/' . $id), array('class' => 'fps-edit')) . '&nbsp;';
		}

		if ($this->ACL->turn(array($this->module, 'up_materials'), false)) {
			$moder_panel .= get_link('', $this->getModuleURL('fix_on_top/' . $id), array('class' => 'fps-star', 'onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
			$moder_panel .= get_link('', $this->getModuleURL('upper/' . $id), array('class' => 'fps-up', 'onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'on_home'), false)) {
			if ($record->getView_on_home() == 1) {
				$moder_panel .= get_link('', $this->getModuleURL('off_home/' . $id), array('class' => 'fps-on', 'onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
			} else {
				$moder_panel .= get_link('', $this->getModuleURL('on_home/' . $id), array('class' => 'fps-off', 'onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
			}
		}

		if ($this->ACL->turn(array($this->module, 'delete_materials'), false)
				|| (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false))) {
			$moder_panel .= get_link('', $this->getModuleURL('delete/' . $id), array('class' => 'fps-delete', 'onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		return $moder_panel;
	}

	/**
	 * RSS
	 *
	 */
	public function rss() {
		include_once ROOT . '/sys/inc/includes/rss.php';
	}

}
