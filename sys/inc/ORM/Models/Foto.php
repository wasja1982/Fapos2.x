<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.0                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Foto Model                    |
| @copyright     ©Andrey Brykin 2010-2012      |
| @last mod      2012/04/26                    |
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
class FotoModel extends FpsModel
{
	public $Table = 'foto';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'author_id',
      	),
        'category' => array(
            'model' => 'FotoSections',
            'type' => 'has_one',
            'foreignKey' => 'category_id',
        ),
    );


	
	public function getNextPrev($id)
	{
		$Register = Register::getInstance();

		$records = array('prev' => array(), 'next' => array());
		$prev = $this->getDbDriver()->select($this->Table, DB_FIRST, array('cond' => array('`id` < ' . $id), 'limit' => 1, 'order' => '`id` DESC'));
		if (!empty($prev[0])) $records['prev'] = new FotoEntity($prev[0]);
		$next = $this->getDbDriver()->select($this->Table, DB_FIRST, array('cond' => array('`id` > ' . $id), 'limit' => 1, 'order' => '`id`'));
		if (!empty($next[0])) $records['next'] = new FotoEntity($next[0]);
		

		return $records;
	}

	function getUserStatistic($user_id) {
		$user_id = intval($user_id);
		if ($user_id > 0) {
			$result = $this->getDbDriver()->select($this->Table, DB_FIRST, array('cond' => array('`author_id`' => $user_id), 'fields' => array('COUNT(*) as cnt'), 'limit' => 1));
			if (is_array($result) && count($result) > 0 && $result[0]['cnt'] > 0) {
				$res = array(
					array(
						'text' => 'Фото',
						'count' => $result[0]['cnt'],
						'url' => get_url('/foto/user/' . $user_id),
					),
				);
				return $res;
			}
		}
		return false;
	}
}