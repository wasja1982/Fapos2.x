<?php
##################################################
##												##
## Author:       Andrey Brykin (Drunya)         ##
## Version:      1.3                            ##
## Project:      CMS                            ##
## package       CMS Fapos                      ##
## subpackege    ACL library                    ##
## copyright     ©Andrey Brykin 2010-2011       ##
## last mod.     2011/12/12                     ##
##################################################


##################################################
##												##
## any partial or not partial extension         ##
## CMS Fapos,without the consent of the         ##
## author, is illegal                           ##
##################################################
## Любое распространение                        ##
## CMS Fapos или ее частей,                     ##
## без согласия автора, является не законным    ##
##################################################

//rules and groups files



class ACL {

	private $rules;
	private $groups;
	private $forums;


	public function __construct($path) {
        include_once $path . 'acl_rules.php';
        include_once $path . 'acl_groups.php';
        include_once $path . 'acl_forums.php';

		$this->rules = $acl_rules;
		$this->groups = $acl_groups;
		$this->forums = $acl_forums;
	}


	public function turn($params, $redirect = true, $group = false) {
		if ($group === false) {
			$user_group = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;
		} else {
			$user_group = (int)$group;
		}
		if (!isset($this->rules[$params[0]]) || !is_array($this->rules[$params[0]])) return false;
		switch (count($params)) {
			case 1:
				$access = (bool)in_array($user_group, $this->rules[$params[0]]);
				break;
			case 2:
			case 3:
				if (!empty($this->rules[$params[0]][$params[1]]) 
				&& is_array($this->rules[$params[0]][$params[1]])) {
					$access = (bool)in_array($user_group, $this->rules[$params[0]][$params[1]]);
				} else {
					$access = false;
				}
				if (count($params) == 3 && $params[0] == 'forum') {
					$cat_id = intval($params[2]);
					if ($cat_id > 0 && in_array($params[1], array('view_themes', 'add_themes', 'add_posts'))) {
						if (isset($this->forums[$params[1]][$cat_id])) {
							$access = (bool)in_array($user_group, $this->forums[$params[1]][$cat_id]);
						}
					}
				}
				break;
			default:
				$access = false;
				break;
		}
	
		if (empty($access) && $redirect) {
			redirect('/error.php?ac=403');
		} else {
			return $access;
		}
	}
	
	
	/**
	*
	*/
	public function save_rules($rules) {
		if ($fopen = fopen(ROOT . '/sys/settings/acl_rules.php', 'w')) {
			fputs($fopen, '<?php ' . "\n" . '$acl_rules = ' . var_export($rules, true) . "\n" . '?>');
			fclose($fopen);
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	*
	*/
	public function save_groups($groups) {
		if ($fopen=@fopen(ROOT . '/sys/settings/acl_groups.php', 'w')) {
			@fputs($fopen, '<?php ' . "\n" . '$acl_groups = ' . var_export($groups, true) . "\n" . '?>');
			@fclose($fopen);
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	*
	*/
	public function save_forums($forums) {
		if ($fopen = fopen(ROOT . '/sys/settings/acl_forums.php', 'w')) {
			fputs($fopen, '<?php ' . "\n" . '$acl_forums = ' . var_export($forums, true) . "\n" . '?>');
			fclose($fopen);
			return true;
		} else {
			return false;
		}
	}
	
	
	public function getGroups()
	{
		$out= array();
		foreach ($this->groups as $k => $v) {
			$out[$k] = $v;
			$out[$k]['id'] = $k;
		}
		return $out;
	}
	
	
	/**
	* @return user group info
	*/
	public function get_group_info() {
		return $this->groups;
	}


    public function getRules()
    {
        return $this->rules;
    }


    public function getForums()
    {
        return $this->forums;
    }
	
	
	/**
	* @param int $id - user group ID
	*
	* @return string group title
	*/
	public function get_user_group($id) {
		if (!empty($this->groups[$id])) return $this->groups[$id];
		return false;
	}
	
	
	
	/**
	 *
	 */
	static public function checkCategoryAccess($catAccessStr) {
		if ($catAccessStr === '') return true;
		$uid = (!empty($_SESSION['user']['status'])) ? intval($_SESSION['user']['status']) : 0;
		
		$accessAr = explode(',', $catAccessStr);
		if (count($accessAr) < 1) return true;
		foreach ($accessAr as $key => $groupId) {
			if ($groupId === $uid) {
				return false;
			}
		}
		return true;
	}

}
?>