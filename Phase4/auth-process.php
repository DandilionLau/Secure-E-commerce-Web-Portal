<?php
// init $_SESSION
session_start();
include_once('lib/csrf.php');
include_once('lib/auth.php');
include_once('lib/db.inc.php');

function ierg4210_login(){
	if (empty($_POST['email']) || empty($_POST['pw']) 
		|| !preg_match("/^[\w=+\-\/][\w='+\-\/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$/", $_POST['email'])
		|| !preg_match("/^[\w@#$%\^\&\*\-]+$/", $_POST['pw']))
		throw new Exception('Wrong Credentials');
	
	// Implement the login logic here'

	$login_success=loginProcess($_POST['email'],$_POST['pw']); //login process is in auth.php
	if ($login_success == 1){
		//prevent session fixation
		session_regenerate_id(true);
		// redirect to admin page
		header('Location: admin.php', true, 302);
		exit();
	} 
	else if($login_success == 2){
		//prevent session fixation
		session_regenerate_id(true);
		// redirect to admin page
		header('Location: index.php', true, 302);
		exit();
	}
	else
		throw new Exception('User name invalid or user password invalid');
}

function ierg4210_logout(){
	// clear the cookies and session
	try{
		setcookie('authtoken','',time()-3600);
		$_SESSION['authtoken']=null;

		setcookie('commontoken','',time()-3600);
		$_SESSION['commontoken']=null;
	}
	echo 'You logout successfully';
	// redirect to login page after logout
	header('Location: login.php');

	exit();
}

function ierg4210_turnin(){
	header('Location: login.php');
	exit();
}


header("Content-type: text/html; charset=utf-8");

try {
	// input validation
	if (empty($_REQUEST['action']) || !preg_match('/^\w+$/', $_REQUEST['action']))
		throw new Exception('Undefined Action');
	
	// check if the form request can present a valid nonce
	if ($_REQUEST['action']=='login')
		csrf_verifyNonce($_REQUEST['action'], $_POST['nonce']);
	
	// run the corresponding function according to action
	if (($returnVal = call_user_func('ierg4210_' . $_REQUEST['action'])) === false) {
		if ($db && $db->errorCode()) 
			error_log(print_r($db->errorInfo(), true));
		throw new Exception('Failed');
	} else {
		// no functions are supposed to return anything
		// echo $returnVal;
	}

} catch(PDOException $e) {
	error_log($e->getMessage());
	header('Refresh: 3; url=login.php?error=db');
	echo '<strong>Error Occurred:</strong> DB <br/>Redirecting to login page in 3 seconds...';
} catch(Exception $e) {
	header('Refresh: 3; url=login.php?error=' . $e->getMessage());
	echo '<strong>Error Occurred:</strong> ' . $e->getMessage() . '<br/>Redirecting to login page in 3 seconds...';
}
?>