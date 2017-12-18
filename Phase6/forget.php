<?php
include_once('lib/csrf.php');
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Change Password Page</title>
</head>
<body>
	<form method="POST" action="auth-process.php?action=<?php echo ($action = 'forget'); ?>">
		E-mail Address: <input type="text" name="email" size="20" required="true" pattern="^[\w=+\-\/][\w=\'+\-\/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$"/>
		<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>"/> 
		<input type="submit" name="ForgotPassword" value=" Request Reset " />
	</form>
</body>
</html>
