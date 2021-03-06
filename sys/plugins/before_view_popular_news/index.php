<?php


class PopNews {
	
	// How match comments to view
	private $limit = 10;
	
	// Wraper for comments
	private $wrap;
	
	// Marker for plugin
	private $marker = '#{{\s*pop_news\s*}}#i';
	
	
	private $DB;

	public function __construct($params) {
		$Register = Register::getInstance();
		$this->DB = $Register['DB'];
		$this->wrap = '<li><a href="%s">%s</a></li>';
	}
	
	
	public function common($params) {
		$output = '';
		
		if (preg_match($this->marker, $params) == 0) return $params;
		
		$Cache = new Cache;
		$Cache->lifeTime = 600;
		if ($Cache->check('pl_pop_news')) {
			$news = $Cache->read('pl_pop_news');
			$news = unserialize($news);
		} else {
			$news = $this->DB->select('news', DB_ALL, array('order' => '`views` DESC', 'limit' => $this->limit));
			$Cache->write(serialize($news), 'pl_pop_news', array());
		}
		
		if (!empty($news)) {
			foreach ($news as $key => $new) {
				$link = get_url('/news/view/' . $new['id']);
				$output .= sprintf($this->wrap, $link, h($new['title']));
			}
		}
			
			
		return preg_replace($this->marker, $output, $params);
	}

}
