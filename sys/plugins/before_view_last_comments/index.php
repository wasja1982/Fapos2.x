<?php


class LastComments {
	
	// How match comments to view
	private $limit = 10;
	
	// Wraper for comments
	//private $wrap = '<li class="point"><b>%s</b> <span style="color:#D6C39B;">Написал в</span><br /> %s</li>';
	private $wrap;
	
	
	
	private $DB;

	public function __construct($params) {
		$Register = Register::getInstance();
		$this->DB = $Register['DB'];
		$this->wrap = '<div class="conmenu2 lastqq">' . "\n" .
			'<div class="lastcomm">' . "\n" .
			'<img class="lastimg" alt="" src="' . get_url('/template/' . Config::read('template')) . 
			'/img/listlast.png">Автор: <span style="color:#587803;">%s</span>, в новости:' . "\n" .
			'<p>%s</p>' . "\n" .
			'</div>' . "\n" .
			'</div>' . "\n";
	}
	
	
	public function common($params) {
		$Register = Register::getInstance();
		
		$output = '';
		
		if (!strpos($params, '{{ last_comments }}')) return $params;
		
		$Cache = new Cache;
		$Cache->lifeTime = 600;
		if ($Cache->check('pl_last_comments')) {
			$comments = $Cache->read('pl_last_comments');
			$comments = unserialize($comments);
		} else {
			$sql = "(SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`message`, b.`title`, (SELECT \"news\") AS skey 
				FROM `" . $this->DB->getFullTableName('news_comments') . "` a
				JOIN `" . $this->DB->getFullTableName('news') . "` b ON b.`id` = a.`entity_id`)
				UNION (SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`message`, b.`title`, (SELECT \"stat\") AS skey 
				FROM `" . $this->DB->getFullTableName('stat_comments') . "` a
				JOIN `" . $this->DB->getFullTableName('stat') . "` b ON b.`id` = a.`entity_id`)
				UNION (SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`message`, b.`title`, (SELECT \"loads\") AS skey 
				FROM `" . $this->DB->getFullTableName('loads_comments') . "` a
				JOIN `" . $this->DB->getFullTableName('loads') . "` b ON b.`id` = a.`entity_id`)
				ORDER BY `date` DESC LIMIT " . $this->limit;
			$comments = $this->DB->query($sql);
			$Cache->write(serialize($comments), 'pl_last_comments', array());
		}
		
		if (!empty($comments)) {
			foreach ($comments as $key => $comm) {
				$link = get_link($comm['title'], '/' . $comm['skey'] . '/view/' . $comm['entity_id']);
				$output .= sprintf($this->wrap, $comm['name'], $link);
			}
		}
			
		
		return str_replace('{{ last_comments }}', $output, $params);
	}

}
