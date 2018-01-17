<!DOCTYPE html>
<html lang="en">
<form action="auth-process.php?action=<?php echo ($action = 'verify'); ?>" method="POST">
	E-mail Address: 
	<input type="text" name="email" size="20" required="true" pattern="^[\w=+\-\/][\w=\'+\-\/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$"/><br />
	New Password: 
	<input type="password" name="password" size="20" required="true" pattern="^[\w@#$%\^\&\*\-]+$"/><br />
	Confirm Password: 
	<input type="password" name="confirmpassword" size="20" required="true" pattern="^[\w@#$%\^\&\*\-]+$"/><br />
	<input type="hidden" name="q" value="<?php if (isset($_GET["q"])) {echo $_GET["q"];} ?>">
	<input type="submit" name="ResetPasswordForm" value=" Reset Password " />
</form>
</html>
