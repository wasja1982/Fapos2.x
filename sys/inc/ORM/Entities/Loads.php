<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.3                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Loads Entity                  |
| @copyright     ©Andrey Brykin 2010-2013      |
| @last mod      2013/04/03                    |
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
class LoadsEntity extends FpsEntity
{
	
	protected $id;
	protected $title;
	protected $main;
	protected $views;
	protected $downloads;
	protected $rate;
	protected $download;
	protected $filename;
	protected $download_url;
	protected $download_url_size;
	protected $date;
	protected $category_id;
	protected $category = null;
	protected $author_id;
	protected $author = null;
	protected $comments;
	protected $comments_ = null;
	protected $attaches = null;
	protected $tags;
	protected $description;
	protected $sourse;
	protected $sourse_email;
	protected $sourse_site;
	protected $commented;
	protected $available;
	protected $view_on_home;
	protected $on_home_top;
	protected $add_fields = null;
	protected $premoder;
	
	
	
	
	public function save()
	{
		$params = array(
			'title' => $this->title,
			'main' => $this->main,
			'views' => intval($this->views),
			'downloads' => intval($this->downloads),
			'rate' => intval($this->rate),
			'download' => $this->download,
			'filename' => $this->filename,
			'download_url' => $this->download_url,
			'download_url_size' => intval($this->download_url_size),
			'date' => $this->date,
			'category_id' => intval($this->category_id),
			'author_id' => intval($this->author_id),
			'comments' => (!empty($this->comments)) ? intval($this->comments) : 0,
			'tags' => (is_array($this->tags)) ? implode(',', $this->tags) : $this->tags,
			'description' => $this->description,
			'sourse' => $this->sourse,
			'sourse_email' => $this->sourse_email,
			'sourse_site' => $this->sourse_site,
			'commented' => (!empty($this->commented)) ? '1' : new Expr("'0'"),
			'available' => (!empty($this->available)) ? '1' : new Expr("'0'"),
			'view_on_home' => (!empty($this->view_on_home)) ? '1' : new Expr("'0'"),
			'on_home_top' => (!empty($this->on_home_top)) ? '1' : new Expr("'0'"),
			'premoder' => (!empty($this->premoder)) ? $this->premoder : 'nochecked',
		);
		if ($this->id) $params['id'] = $this->id;
		$Register = Register::getInstance();
		return ($Register['DB']->save('loads', $params));
	}
	
	
	
	public function delete()
	{ 
		$Register = Register::getInstance();
		
		$attachesModel = $Register['ModManager']->getModelInstance('LoadsAttaches');
		$commentsModel = $Register['ModManager']->getModelInstance('Comments');
		$addContentModel = $Register['ModManager']->getModelInstance('LoadsAddContent');
		
		$attachesModel->deleteByParentId($this->id);
		$commentsModel->deleteByParentId($this->id, 'loads');
		$addContentModel->deleteByParentId($this->id);

        if (file_exists(ROOT . '/sys/files/loads/' . $this->download)) {
            _unlink(ROOT . '/sys/files/loads/' . $this->download);
        }

		$Register['DB']->delete('loads', array('id' => $this->id));
	}



    /**
     * @param $comments
     */
	public function setComments_($comments)
    {
        $this->comments_ = $comments;
    }



    /**
     * @return array
     */
    public function getComments_()
   	{

        $this->checkProperty('comments_');
   		return $this->comments_;
   	}



    /**
     * @param $comments
     */
	public function setAttaches($attaches)
    {
        $this->attaches = $attaches;
    }



    /**
     * @return array
     */
    public function getAttaches()
   	{

        $this->checkProperty('attaches');
   		return $this->attaches;
   	}



    /**
     * @param $author
     */
    public function setAuthor($author)
   	{
   		$this->author = $author;
   	}



    /**
     * @return object
     */
	public function getAuthor()
	{
        if (!$this->checkProperty('author')) {
			if (!$this->getAuthor_id()) {
                $Register = Register::getInstance();
				$this->author = $Register['ModManager']->getEntityInstance('users');
			} else {
                $Register = Register::getInstance();
				$usersModel = $Register['ModManager']->getModelInstance('Users');
				$this->author = $usersModel->getById($this->author_id);
				// $Model = new LoadsModel('loads');
				// $this->author = $Model->getAuthorByEntity($this); // TODO (function is not exists)
			}
        }
		return $this->author;
	}
	
	

    /**
     * @param $category
     */
    public function setCategory($category)
   	{
   		$this->category = $category;
   	}



	/**
     * @return object
     */
	public function getCategory()
	{
        if (!$this->checkProperty($this->category)) {
			if (!$this->getCategory_id()) {
                $Register = Register::getInstance();
				$this->category = $Register['ModManager']->getEntityInstance('LoadsSections');
			} else {
                $Register = Register::getInstance();
				$catsModel = $Register['ModManager']->getModelInstance('LoadsSections');
				$this->category = $catsModel->getById($this->category_id);
				// $Model = new LoadsModel('loads');
				// $this->category = $Model->getCategoryByEntity($this); // TODO (function is not exists)
			}
        }
		return $this->category;
	}

}