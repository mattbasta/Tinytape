<?php

view_manager::set_value('REDIRECT', isset($_GET['redirect'])?$_GET['redirect']:'');

class methods {
	public function __default() {
		
		view_manager::set_value("TITLE", "Login to Tinytape");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "login/login");
		
		return view_manager::render_as_httpresponse();
		
	}
	public function signup($done = "") {
		global $session, $r;
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		
		if(!empty($done) && $session->start_time !== false && $session->logged_in) {
			$start_time = $session->start_time;
			
			view_manager::add_view(VIEW_PREFIX . "login/signup_done");
			view_manager::set_value("TITLE", "Thanks bro. Tinytape loves you.");
			
			$duration = $_SERVER["REQUEST_TIME"] - $start_time;
			view_manager::set_value("DURATION", $duration);
			view_manager::set_value("PASSED_DURATION", $duration < 61);
			view_manager::set_value("DURATION_MINUTES", floor($duration / 60));
			view_manager::set_value("DURATION_SECONDS", $duration % 60);
			
			if($duration < 61)
				bless_badge("fast_signup");
			else
				bless_badge("slow_signup");
			
			$session->start_time = false;
			return view_manager::render_as_httpresponse();
		}
		
		view_manager::add_view(VIEW_PREFIX . "login/signup");
		
		$num1 = rand(5,10);
		$num2 = rand(11,90);
		$pm = rand(0,1) * 2 - 1;
		$session->captcha = $num2 + ($pm * $num1);
		$session->start_time = time();
		
		view_manager::set_value('MATH', $num2 . ($pm < 0 ? ' - ' : ' + ') . $num1 . ' = ');
		view_manager::set_value("TITLE", "Sign up for a Tinytape account");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function logout() {
		global $session;
		$session->destroy();
		
		header("Location: " . URL_PREFIX);
		
		return false;
		
	}
	
	public function go($action) {
		global $db, $session, $r;
		
		$redirect_to = URL_PREFIX . 'account';
		
		switch($action) {
			case 'login':
				
				$username = strtolower($_REQUEST['username']);
				
				$user_table = $db->get_table('users');
				$user = $user_table->fetch(
					array(
						'username'=>$username, // Not to worry, Cloud escapes this out for us
						'password'=>sha1($_REQUEST['password'])
					),
					FETCH_SINGLE_ARRAY,
					array(
						'columns'=>array('id', 'admin')
					)
				);
				
				// Tell the user that they did something wrong
				if($user === false) {
					header('Location: ' . URL_PREFIX . 'login/?invalid');
					return false;
				}
				
				$session->logged_in = true;
				$session->username = $username;
				$session->admin = (bool)$user["admin"];
				$session->id = $user["id"];
				$session->song_count = 0;
				
				break;
			case 'signup':
				$user_table = $db->get_table('users');
				$username = strtolower($_REQUEST['username']);
				
				if(empty($_REQUEST['email']) ||
				   strpos($_REQUEST["email"], ";") !== false ||
				   strpos($_REQUEST["email"], ",") !== false ||
				   strpos($_REQUEST["email"], " ") !== false)
					$error = 'email';
				elseif($user_table->fetch_exists(
					cloud::_or(
						cloud::_comp(cloud::_st('email'),'=',$_REQUEST['email']),
						cloud::_comp(cloud::_st('username'),'=',$username)
					)
				))
					$error .= '-exists';
					
				if($_REQUEST['password'] != $_REQUEST['confirm'])
					$error .= '-again';
				if(strlen($_REQUEST['password']) < 7)
					$error .= '-plen';
				if($session->captcha != $_REQUEST['math'])
					$error .= '-captcha';
				
				if(!empty($error)) {
					header('Location: ' . URL_PREFIX . 'login/signup?invalid=' . $error);
					exit;
				}
				
				$user_table->insert_row(
					0,
					array(
						'username'=>$username,
						'email'=>$_REQUEST['email'],
						'password'=>sha1($_REQUEST['password']) // For reverse compatibility + security
					)
				);
				
				$session->logged_in = true;
				$session->admin = false;
				//$session->email = $_REQUEST['email'];
				$session->username = $username;
				
				$r->sAdd("tinytape_users", $username);
				$r->zIncrBy("tinytape_logincount", 1, $username);
				
				$redirect_to = URL_PREFIX . "login/signup/done";
				
				break;
			default:
				load_page('404', 404);
				return false;
		}
		
		if(!isset($_REQUEST["redirect"]))
			header('Location: ' . $redirect_to);
		else
			header("Location: {$_REQUEST['redirect']}");
		
		return false;
		
	}
}
