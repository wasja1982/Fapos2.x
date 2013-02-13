<?php

$cache_key = $this->module . '_rss';
$cache_tags = array(
	'module_' . $this->module,
	'action_rss',
);


$check = Config::read('rss_' . $this->module, 'common');
if (!$check) {
	$html = '<?xml version="1.0" encoding="UTF-8"?>';
	$html .= '<rss version="2.0" />';
} else {
	if ($this->cached && $this->Cache->check($cache_key)) {
		$html = $this->Cache->read($cache_key);
	} else {
		$sitename = '/';
		if (!empty($_SERVER['SERVER_NAME'])) {
			$sitename = 'http://' . $_SERVER['SERVER_NAME'] . '';
		}

		$html = '<?xml version="1.0" encoding="UTF-8"?>';
		$html .= '<rss version="2.0">';
		$html .= '<channel>';
		$html .= '<title>' . h(Config::read('title', $this->module)) . '</title>';
		$html .= '<link>' . $sitename . $this->module . '/</link>';
		$html .= '<description>' . h(Config::read('description', $this->module)) . '</description>';
		$html .= '<pubDate>' . date('r') . '</pubDate>';
		$html .= '<generator>FPS RSS Generator (Fapos CMS)</generator>';


		$this->Model->bindModel('category');
		$this->Model->bindModel('author');
		$this->Model->bindModel('attaches');
		$records = $this->Model->getCollection(
			array(), 
			array(
				'limit' => Config::read('rss_cnt', 'common'),
				'order' => 'id DESC',
			)
		);

		if (!empty($records) && is_array($records)) {
			$html .= '<lastBuildDate>' . date('r', strtotime($records[0]->getDate())) . '</lastBuildDate>';
			foreach ($records as $record) { 
				$html .= '<item>';
				$html .= '<link>' . $sitename . get_url(entryUrl($record, $this->module)) . '</link>';
				$html .= '<pubDate>' . date('r', strtotime($record->getDate())) . '</pubDate>';
				$html .= '<title>' . $record->getTitle() . '</title>';
				$announce = $this->Textarier->getAnnounce($record->getMain(), null, 0, Config::read('rss_lenght', 'common'), $record);
				$atattaches = ($record->getAttaches() && count($record->getAttaches())) ? $record->getAttaches() : array();
				if (count($atattaches) > 0) {
					foreach ($atattaches as $attach) {
						if ($attach->getIs_image() == '1') {
							$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number(), $record->getSkey());
						}
					}
				}
				$html .= '<description><![CDATA[' . $announce . '<br />';
				$html .= 'Автор: ' . $record->getAuthor()->getName() . '<br />]]></description>';
				$html .= '<category>' . $record->getCategory()->getTitle() . '</category>';
				$html .= '<guid>' . $sitename . $this->module . '/view/' . $record->getId() . '</guid>';
				$html .= '</item>';
			}
		}

		$html .= '</channel>';
		$html .= '</rss>';

		$this->Cache->write($html, $cache_key, $cache_tags);
	}
}

echo $html; 

?>