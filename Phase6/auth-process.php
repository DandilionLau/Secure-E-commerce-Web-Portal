<?php
// init $_SESSION
session_start();
include_once('lib/csrf.php');
include_once('lib/auth.php');

function signinProcess($email, $password, $flag){
		$db=newDB();
		$salt = uniqid(mt_rand(), true);
		$saltPassword=hash_hmac('sha1',$password, $salt);

		$q=$db->prepare('INSERT INTO account (salt, password, flag, email) VALUES (?, ?, ?, ?)');
		$q->execute(array($salt, $saltPassword, $flag, $email));
		return 1;
}

function changeProcess($email, $password){
		$db=newDB();
		$q=$db->prepare('SELECT * FROM account WHERE email = ?');
		$q->execute(array($email));
		if($r=$q->fetch())
		{
			$saltPassword=hash_hmac('sha1', $password, $r['salt']);
			$q=$db->prepare('UPDATE account SET password = ? WHERE email = ?');
			$q->execute(array($saltPassword, $email));
		}
		return 1;
}

function ierg4210_login(){
	if (empty($_POST['email']) || empty($_POST['pw']) 
		|| !preg_match("/^[\w=+\-\/][\w='+\-\/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$/", $_POST['email'])
		|| !preg_match("/^[\w@#$%\^\&\*\-]+$/", $_POST['pw']))
		throw new Exception('Wrong Credentials');
	
	// Implement the login logic here
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
	setcookie('authtoken','',time()-3600);
	$_SESSION['authtoken']=null;
	setcookie('commontoken','',time()-3600);
	$_SESSION['commontoken']=null;
	echo 'You logout successfully';
	// redirect to login page after logout
	header('Location: login.php');
	exit();
}


function ierg4210_change(){
	if (empty($_POST['email']) || empty($_POST['pw']) 
		|| !preg_match("/^[\w=+\-\/][\w='+\-\/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$/", $_POST['email'])
		|| !preg_match("/^[\w@#$%\^\&\*\-]+$/", $_POST['pw']))
		throw new Exception('Wrong Credentials');
	
	if($_POST['newpw'] == $_POST['pw']){
		throw new Exception('Old password and new password cannot be the same.');
	}

	// Implement the login logic here
	$login_success=loginProcess($_POST['email'],$_POST['pw']); //login process is in auth.php
	if (($login_success == 1) || ($login_success == 2)){
		$change_success = changeProcess($_POST['email'],$_POST['newpw']);
		if($change_success)
		{
			session_regenerate_id(true);
			// redirect to admin page
			header('Location: login.php', true, 302);
			ierg4210_logout();
			exit();
		}
	}
	 else
		throw new Exception('User name invalid or user password invalid');
}

function ierg4210_signin(){
	if (empty($_POST['email']) || empty($_POST['pw']) 
		|| !preg_match("/^[\w=+\-\/][\w='+\-\/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$/", $_POST['email'])
		|| !preg_match("/^[\w@#$%\^\&\*\-]+$/", $_POST['pw']))
		throw new Exception('Wrong Credentials');
	
	if($_POST['repw'] != $_POST['pw']){
		throw new Exception('Password Mismatches');
	}

	//throw new Exception('Test');

	$signin_success=signinProcess($_POST['email'],$_POST['pw'],1);

	if ($signin_success){
		//prevent session fixation
		session_regenerate_id(true);
		// redirect to admin page
		header('Location: login.php', true, 302);
		exit();
	}
	 else
		throw new Exception('Not Able To Sign Up');
	// Implement the login logic here
}

function ierg4210_forget(){

	$db=newDB();

	// Was the form submitted?
	if (isset($_POST["ForgotPassword"])) {
	
		// Harvest submitted e-mail address
		if (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
			$email = $_POST["email"];
		}
		else{
			throw new Exception('Email is not valid');
		}

		// Check to see if a user exists with this e-mail
		$q=$db->prepare('SELECT * FROM account WHERE email = ?');
		$q->execute(array($email));
		
		if ($r=$q->fetch())
		{
			// Create a unique salt. This will never leave PHP unencrypted.
			$salt = $r["salt"];

			// Create the unique user password reset key
			$nounce = hash_hmac('sha1', $email, $salt);

			// Create a url which we will direct them to reset their password
			$pwrurl = "https://s56.ierg4210.ie.cuhk.edu.hk/reset_password.php?q=".$nounce;
			
			// Mail them their key
			$mailbody = "Dear user,\n\nIf this e-mail does not apply to you please ignore it. It appears that you have requested a password reset at our website www.yoursitehere.com\n\nTo reset your password, please click the link below. If you cannot click it, please paste it into your web browser's address bar.\n\n" . $pwrurl . "\n\nThanks,\nThe Administration";
			mail($email, "s56.ierg4210.ie.cuhk.edu.hk - Password Reset", $mailbody);
			echo "Your password recovery key has been sent to your e-mail address.";
			
		}
		else
			echo "No user with that e-mail address exists.";
	}	
}

function ierg4210_verify(){
	// Connect to MySQL
    $db=newDB();

	// Was the form submitted?
	if (isset($_POST["ResetPasswordForm"]))
	{
		// Gather the post data
		$pw = $_POST["password"];
		$confpw = $_POST["confirmpassword"];
		$passnounce = $_POST["q"];
		// Use the same salt from the forgot_password.php file

		$email = $_POST["email"];

		if($confpw != $pw){
			throw new Exception('Two Inputs must be the same');
		}

		$q=$db->prepare('SELECT * FROM account WHERE email = ?');
		$q->execute(array($email));

		if ($r=$q->fetch())
		{
			$salt = $r["salt"];
			$newnounce = hash_hmac('sha1', $email, $salt);
			//throw new Exception($email.$salt);
			if($newnounce == $passnounce){
				$newsalt = uniqid(mt_rand(), true);

				$saltPassword=hash_hmac('sha1', $pw, $newsalt);

				$q=$db->prepare('UPDATE account SET password = ? WHERE email = ?');
				$q->execute(array($saltPassword, $email));

				$q=$db->prepare('UPDATE account SET salt = ? WHERE email = ?');
				$q->execute(array($newsalt, $email));

				session_regenerate_id(true);
				// redirect to admin page
				header('Location: login.php', true, 302);
				ierg4210_logout();
				exit();
			}
			else{
				throw new Exception('Your password reset key is invalid.');
			}
		}
		else{
				throw new Exception('Your email account does not exist.');
		}
	}
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