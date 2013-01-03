<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.0                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    LoadsComments Model           |
| @copyleft      сообщество FaposCMS           |
| @last mod      2012/08/04                    |
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
class FotoCommentsModel extends FpsModel
{
	
    public $Table = 'foto_comments';
    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'user_id',
      	),
        'parent_entity' => array(
            'model' => 'Foto',
            'type' => 'has_one',
            'foreignKey' => 'entity_id',
        ),
    );

	
	
	public function getByEntity($entity)
	{
		$this->bindModel('Users');
		$params['entity_id'] = $entity->getId();
		$news = $this->getCollection($params);
		return $news;
	}
	
}