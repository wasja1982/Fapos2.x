<?php
##################################################
##												##
## @Author:       Andrey Brykin (Drunya)        ##
## @Version:      1.3                           ##
## @Project:      CMS                           ##
## @package       CMS Fapos                     ##
## @subpackege    Admin module                  ##
## @copyright     ©Andrey Brykin 2010-2013      ##
## @Last mod.     2013/07/17                    ##
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


header('Content-Type: text/html; charset=utf-8');





$FpsDB = $Register['DB']; //TODO
$ACL = $Register['ACL'];








if (ADM_REFER_PROTECTED == 1) {
	$script_name = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
	$script_name = strrchr($script_name, '/');
	if ($script_name != '/index.php') {
		$referer = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
		preg_match('#^http://([^/]+)#', $referer, $match);
		if (empty($match[1]) || $match[1] != $_SERVER['SERVER_NAME'])
			redirect('/admin/index.php');
	}
}


///if (empty($_SESSION['user'])) redirect('/');
if (!isset($_SESSION['adm_panel_authorize']) || $_SESSION['adm_panel_authorize'] < time() || empty($_SESSION['user'])) {
	if (isset($_POST['send']) && isset($_POST['login']) && isset($_POST['passwd'])) {
		$errors = '';
		$login = strtolower(trim($_POST['login']));
		$pass = trim($_POST['passwd']);

		if (empty($login)) $errors .= '<li>Заполните поле "Логин"</li>';
		if (empty($pass)) $errors .= '<li>Заполните поле "Пароль"</li>';


		if (empty($errors)) {
			/*
			if ($login != strtolower($_SESSION['user']['name']) || md5($pass) != $_SESSION['user']['passw']) 
				$errors .= '<li>Не верный Пароль или Логин</li>';
			*/
			$users = $FpsDB->select('users', DB_FIRST, array('cond' => array('name' => $login)));
			
			$check_password = false;
			if (count($users) > 0 && !empty($users[0])) {
				$check_password = checkPassword($users[0]['passw'], $pass);
			}
			
			if (count($users) < 1 || !$check_password) {
				$errors .= '<li>Не верный Пароль или Логин</li>';
			} else {
				//turn access
				$ACL->turn(array('panel', 'entry'), true, $users[0]['status']);
			}

			if (empty($errors)) {
				$_SESSION['user'] = $users[0];
				$_SESSION['adm_panel_authorize'] = (time() + Config::read('session_time', 'secure'));
				redirect('/admin/');
			}
		}
	}


    $pageTitle = 'Авторизация в панели Администрирования';
    $pageNav = '';
    $pageNavr = '';
?>



<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Fapos Admin Panel Authorization</title>
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
	<link rel="StyleSheet" type="text/css" href="template/css/style.css" />
	<script language="JavaScript" type="text/javascript" src="../sys/js/jquery-1.8.1.min.js"></script>
	<script type="text/javascript">
	</script>
</head>
<body>
	<div id="login-wrapper">
		<div class="shadow-mask"></div>
		<div class="form">
			<div class="title">Авторизация</div>
			<form method="POST" action="" >
				<div class="items">
					<?php 
					if (!empty($errors)) {
						echo '<ul class="error">' . $errors . '</ul>';
						unset($errors);
					}
					?>
					<div class="item"><span>Логин</span><input name="login" type="text" /></div>
					<div class="item"><span>Пароль</span><input name="passwd" type="password" /></div>
				</div>
				<div class="submit"><input type="submit" name="send" value="" /></div>
			</form>
		</div>
	</div>
</body>
</html>






<?php	
	//include_once 'template/footer.php';
	die();



} else if (!empty($_SESSION['adm_panel_authorize'])) {
	$_SESSION['adm_panel_authorize'] = (time() + Config::read('session_time', 'secure'));


	if (!empty($ACL)) $ACL = $Register['ACL'];

	if ($ACL->turn(array('panel', 'restricted_access'), false)) {

		$url = preg_replace('#^.*/([^/]+)\.\w{2,5}$#i', "$1", $_SERVER['SCRIPT_NAME']);
		//var_dump($url);
		if (!empty($url) && $url != 'index') {
			if (!$ACL->turn(array('panel', 'restricted_access_' . $url), false)) {
				$_SESSION['message'] = __('Permission denied');
				redirect('/admin/');
			}
		}
	}
}






if (!empty($_GET['install'])) {
	$instMod = (string)$_GET['install'];
	if (!empty($instMod) && preg_match('#^[a-z]+$#i', $instMod)) {
		$ModulesInstaller = new FpsModuleInstaller();
		$ModulesInstaller->installModule($instMod);
	}
}




function cmpAnkor($a, $b) {
	if (is_array($a) && is_array($b) && isset($a['ankor']) && isset($b['ankor'])) {
		if ($a['ankor'] == $b['ankor']) {
			return 0;
		}
		return ($a['ankor'] < $b['ankor']) ? -1 : 1;
	} else {
		return 0;
	}
}


function getAdmFrontMenuParams()
{
    $out = array();
    $modules = glob(ROOT . '/modules/*', GLOB_ONLYDIR);
    if (count($modules)) {
        foreach ($modules as $key => $modPath) {
            if (file_exists($modPath . '/info.php')) {
                include($modPath . '/info.php');
                if (isset($menuInfo)) {
                    $mod = basename($modPath);
                    $out[$mod] = $menuInfo;
                }
            }
        }
    }
	uasort($out, 'cmpAnkor');
    return $out;
}
?>