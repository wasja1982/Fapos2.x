<?php
##################################################
##												##
## @Author:       Andrey Brykin (Drunya)        ##
## @Version:      1.6.1                         ##
## @Project:      CMS                           ##
## @package       CMS Fapos                     ##
## @subpackege    Admin Panel module            ##
## @copyright     ©Andrey Brykin 2010-2013      ##
## @last mod.     2013/07/16                    ##
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


include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';
$pageTitle = 'Премодерация материалов';
$Register = Register::getInstance();
$config = $Register['Config']->read('all');
$premoder_types = array('0' => 'rejected', '1' => 'confirmed');


function insertImageAttach($message, $filename, $number, $module)
{
	$preview_link = get_url('/sys/files/' . $module . '/' . $filename);
	$size_x = Config::read('img_size_x', $module);
	$size_y = Config::read('img_size_y', $module);
	$str = '<img %s style="max-width:' . (!empty($size_x) ? $size_x : 150) . 'px; max-height:' . (!empty($size_y) ? $size_y : 150) . 'px;" src="' . $preview_link . '" />';
	$from = array(
		'{IMAGE' . $number . '}', 
		'{LIMAGE' . $number . '}', 
		'{RIMAGE' . $number . '}', 
	);
	$to = array(
		sprintf($str, ''), 
		sprintf($str, 'align="left"'), 
		sprintf($str, 'align="right"'), 
	);
	return str_replace($from, $to, $message);
}

$output = '';
$module = $_GET['m'];
if (in_array($module, array('news', 'stat', 'loads'))) {
	$Model = $Register['ModManager']->getModelInstance($module);

	$pageTitle = __($module) . ' - ' . $pageTitle;

	// Save settings

	if (!empty($_GET['id']) && !empty($_GET['status'])) {
		
		$entity = $Model->getById(intval($_GET['id']));
		if (!empty($entity)) {
			
			$status = strtolower((string)$_GET['status']);
			if (in_array($status, $premoder_types)) {
				$available = array_search($status, $premoder_types);
				if ($available !== false) {
					$entity->setAvailable($available);
					$entity->save();
				}
			}
			$_SESSION['message'] = __('Saved');
			
			//clean cache
			$Cache = new Cache;
			$Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $module));
		} else {
			$_SESSION['message'] = __('Some error occurred');
		}
		redirect('/admin/premoder.php?m=' . $module);
	}



	$Model->bindModel('attaches');
	$Model->bindModel('author');
	$Model->bindModel('category');
	$premoder_entities = $Model->getCollection(array(
		'available = \'0\'',
	));
	


	if (is_array($premoder_entities) && count($premoder_entities)) {
		foreach ($premoder_entities as $premoder_ent) {
			
			
			$announce = $Register['PrintText']->getAnnounce($premoder_ent->getMain(), null, 0, Config::read('announce_lenght', $module), $premoder_ent);
			
			// replace image tags in text
			$attaches = $premoder_ent->getAttaches();
			if (!empty($attaches) && count($attaches) > 0) {
				$attachDir = ROOT . '/sys/files/' . $module . '/';
				foreach ($attaches as $attach) {
					if ($attach->getIs_image() == 1 && file_exists($attachDir . $attach->getFilename())) {
					
						$announce = insertImageAttach(
							$announce, 
							$attach->getFilename(), 
							$attach->getAttach_number(),
							$module
						);
					}
				}
			}
			
			
			$output .= '<div class="setting-item">
				<div class="left">
				' . h($premoder_ent->getTitle()) . '
				</div>
				<div class="right" style="position:relative; border-right: 1px solid #E1E1E1;">
					<div style="width:500px;">' . $announce . '</div>
					</div><div style="position:relative; float:left; padding-left:20px;">' . /* get_link('', 'admin/premoder.php?m=' . $module . '&id=' . $premoder_ent->getId() . '&status=rejected',
							array(
								'class' => 'off', 
								'title' => 'Reject', 
								'onClick' => "return confirm('" . __('Are you sure') . "')",
								'style' => 'position:absolute; top:21px;',
							)) . '&nbsp;' . '
					' . */ get_link('', '/admin/premoder.php?m=' . $module . '&id=' . $premoder_ent->getId() . '&status=confirmed',
							array(
								'class' => 'on', 
								'title' => 'Confirm', 
								'onClick' => "return confirm('" . __('Are you sure') . "')",
								'style' => /* 'margin-left:15px; ' . */ ' position:absolute; top:21px;',
							)) . '&nbsp;</div>' . '
				
				<div class="clear"></div>
			</div>';
		}
	} else {
		$_SESSION['message'] = __('Not found material for premoderation');
	}

} else {
	$_SESSION['message'] = __('Module not found');
}

$pageNav = $pageTitle;
$pageNavr = '';
include_once ROOT . '/admin/template/header.php';
?>


<?php if (!empty($_SESSION['message'])): ?>
<div class="warning"><?php echo $_SESSION['message'] ?></div>
<?php unset($_SESSION['message']); endif; ?>


<form method="POST" action="premoder.php?m=<?php echo $module; ?>" enctype="multipart/form-data">
<div class="list">
	<div class="title"><?php echo $pageNav; ?></div>
	<div class="level1">
		<div class="head">
			<div class="title settings">Заголовок</div>
			<div class="title-r">Текст</div>
			<div class="clear"></div>
		</div>
		<div class="items">
			<?php echo $output; ?>
			<!--<div class="setting-item">
				<div class="left">
				</div>
				<div class="right">
					<input class="save-button" type="submit" name="send" value="Сохранить" />
				</div>
				<div class="clear"></div>
			</div>-->
		</div>
	</div>
</div>
</form>



<?php /*echo '<form method="POST" action="settings.php?m=' . $module . '" enctype="multipart/form-data">*/ ?>



<?php include_once 'template/footer.php'; ?>
