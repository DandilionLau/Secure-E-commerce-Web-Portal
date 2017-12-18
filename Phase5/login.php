<?php
include_once('lib/csrf.php');
include_once('lib/auth.php');

if ($_SESSION['authtoken']){
	//header('Refresh:1; admin.php');
	//echo '<b>You are already logined</b> <br>Redirecting you to admin page in 1 second...';
	header('Location: admin.php');
	exit();
}
else if($_SESSION['commontoken']){
	header('Location: index.php');
	exit();
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Login Panel</title>
</head>
<body>
<h1>Login Panel</h1>
<fieldset>
	<legend>Login</legend>
	<form id="loginForm" method="POST" action="auth-process.php?action=<?php echo ($action = 'login'); ?>">
		<label for="email">Email:</label>
		<div>
		<input type="text" name="email" required="true" pattern="^[\w=+\-\/][\w=\'+\-\/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$" />
		</div>
		<label for="pw">Password:</label>
		<div>
		<input type="password" name="pw" required="true" pattern="^[\w@#$%\^\&\*\-]+$" />
		</div>
		<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>"/>
		<input type="submit" value="Login" />
	</form>
</fieldset>
</body>
</html>