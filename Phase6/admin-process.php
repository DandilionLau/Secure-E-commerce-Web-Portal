<?php
include_once('lib/db.inc.php');
include_once('lib/auth.php');

function validate()
{
	if (!auth()){
		//header('Refresh:3; login.php');
		//echo 'You are not logined <br>Redirecting you to login page in 3 second...';
		header('Location: login.php');
		exit();
	}
}

function ierg4210_prod_fetchlimit() {
	// DB manipulation
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("SELECT * FROM products LIMIT 6;");
	if ($q->execute())
		return $q->fetchAll();
}

function ierg4210_prod_fetchall() {
	// DB manipulation
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("SELECT * FROM products LIMIT 100;");
	if ($q->execute())
		return $q->fetchAll();
}

function ierg4210_cat_fetchall() {
	// DB manipulation
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("SELECT * FROM categories LIMIT 100;");
	if ($q->execute())
		return $q->fetchAll();
}

function ierg4210_cat_fetch() {
	// DB manipulation
	$_GET['catid'] = (int) $_GET['catid'];
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("SELECT * FROM categories WHERE catid=?;");
	if ($q->execute(array($_GET['catid'])))
		return $q->fetchAll();
}

function ierg4210_cat_select() {
	// DB manipulation
	$_GET['catid'] = (int) $_GET['catid'];
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("SELECT * FROM products WHERE catid=? LIMIT 9;");
	if ($q->execute(array($_GET['catid'])))
		return $q->fetchAll();
}

function ierg4210_prod_select() {
	// DB manipulation
	$_GET['pid'] = (int) $_GET['pid'];
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("SELECT * FROM products WHERE pid=?;");
	if ($q->execute(array($_GET['pid'])))
		return $q->fetchAll();
}

function ierg4210_prod_list_fetch() {
	$array = json_decode($_POST['list_of_pid']);
	global $db;
	$db = ierg4210_DB();
	$a = sprintf('SELECT name, price, pid FROM products WHERE pid IN (%s);',implode(',',array_fill(1, count($array), '?'))); 
	
	$q = $db->prepare($a);
	if ($q->execute($array))
		return $q->fetchAll();
}

function ierg4210_cat_insert() {
	validate();
	//TODO: input validation or sanitization using Regex

    if (!preg_match('/^[\w\- ]+$/', $_POST['name']))
		throw new Exception("invalid-name");

	// DB manipulation
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("INSERT INTO categories (name) VALUES (?)");
	return $q->execute(array($_POST['name']));
}

function ierg4210_cat_edit() {
	validate();
	// TODO: complete the rest of this function; it's now always says "successful" without doing anything
	if (!preg_match('/^[\w\- ]+$/', $_POST['name']))
		throw new Exception("invalid-name");

  $_POST['catid']=(int)$_POST['catid'];

	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("UPDATE categories SET name = ? WHERE catid = ?");
	return $q->execute(array($_POST['name'], $_POST['catid']));
}

function ierg4210_cat_delete() {
	validate();
	// input validation or sanitization
	$_POST['catid'] = (int) $_POST['catid'];

	// DB manipulation
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("DELETE FROM categories WHERE catid = ?");
	return $q->execute(array($_POST['catid']));
}

// Since this form will take file upload, we use the tranditional (simpler) rather than AJAX form submission.
// Therefore, after handling the request (DB insert and file copy), this function then redirects back to admin.html
function ierg4210_prod_insert() {
	validate();
	// input validation or sanitization
	// DB manipulation
	global $db;
	$db = ierg4210_DB();


	// TODO: complete the rest of the INSERT command
	if (!preg_match('/^\d*$/', $_POST['catid']))
		throw new Exception("invalid-catid");
	$_POST['catid'] = (int) $_POST['catid'];
	if (!preg_match('/^[\w\- ]+$/', $_POST['name']))
		throw new Exception("invalid-name");
	if (!preg_match('/^[\d\.]+$/', $_POST['price']))
		throw new Exception("invalid-price");
	if (!preg_match('/^[\w\- ]+$/', $_POST['description']))
		throw new Exception("invalid-textt");

	$sql="INSERT INTO products (catid, name, price, description) VALUES (?, ?, ?, ?)";
	$q = $db->prepare($sql);
	//$q->execute(array($_POST['catid'],$_POST['name'],$_POST['price'],$_POST['description']));

	// The lastInsertId() function returns the pid (primary key) resulted by the last INSERT command
	//$lastId = $db->lastInsertId();
	// Copy the uploaded file to a folder which can be publicly accessible at incl/img/[pid].jpg
	if ($_FILES["file"]["error"] == 0
		&& $_FILES["file"]["type"] == "image/jpeg"
		&& mime_content_type($_FILES["file"]["tmp_name"]) == "image/jpeg"
		&& $_FILES["file"]["size"] < 5000000) {

		$q->execute(array($_POST['catid'],$_POST['name'],$_POST['price'],$_POST['description']));
		$lastId = $db->lastInsertId();
		// Note: Take care of the permission of destination folder (hints: current user is apache)
		if (move_uploaded_file($_FILES["file"]["tmp_name"], "/var/www/html/incl/img/" . $lastId . ".jpg")) {
		// redirect back to original page; you may comment it during debug

			header('Location: admin.php');
			exit();
		}
	}
	// Only an invalid file will result in the execution below
	// To replace the content-type header which was json and output an error message
	//header('Content-Type: text/html; charset=utf-8');
	//echo 'Invalid file detected. <br/><a href="javascript:history.back();">Back to admin panel.</a>';
	//exit();
}

// TODO: add other functions here to make the whole application complete


function ierg4210_prod_edit() {
	validate();
	// input validation or sanitization
	// DB manipulation
	global $db;
	$db = ierg4210_DB();

	// TODO: complete the rest of the INSERT command
	if (!preg_match('/^\d*$/', $_POST['catid']))
		throw new Exception("invalid-catid");
	$_POST['catid'] = (int) $_POST['catid'];
	$_POST['pid']=(int)$_POST['pid'];
	if (!preg_match('/^[\w\- ]+$/', $_POST['name']))
		throw new Exception("invalid-name");
	if (!preg_match('/^[\d\.]+$/', $_POST['price']))
		throw new Exception("invalid-price");
	$_POST['price']=(int)$_POST['price'];
	if (!preg_match('/^[\w\- ]+$/', $_POST['description']))
		throw new Exception("invalid-textt");

	$sql="UPDATE products SET catid=?, name=?, price=?, description=? WHERE pid=?";
	$q = $db->prepare($sql);
	//$q->execute(array($_POST['catid'],$_POST['name'],$_POST['price'],$_POST['description']));

	// The lastInsertId() function returns the pid (primary key) resulted by the last INSERT command
	//$lastId = $db->lastInsertId();
	// Copy the uploaded file to a folder which can be publicly accessible at incl/img/[pid].jpg
	if ($_FILES["file"]["error"] == 0
		&& $_FILES["file"]["type"] == "image/jpeg"
		&& mime_content_type($_FILES["file"]["tmp_name"]) == "image/jpeg"
		&& $_FILES["file"]["size"] < 5000000) {

		$q->execute(array($_POST['catid'],$_POST['name'],$_POST['price'],$_POST['description'],$_POST['pid']));
		//$lastId = $db->lastInsertId();
		// Note: Take care of the permission of destination folder (hints: current user is apache)
		if (move_uploaded_file($_FILES["file"]["tmp_name"], "/var/www/html/incl/img/" . $_POST['pid'] . ".jpg")) {
		// redirect back to original page; you may comment it during debug

			header('Location: admin.php');
			exit();
		}
	}
	// Only an invalid file will result in the execution below
	// To replace the content-type header which was json and output an error message
	//header('Content-Type: text/html; charset=utf-8');
	//echo 'Invalid file detected. <br/><a href="javascript:history.back();">Back to admin panel.</a>';
	//exit();
}

function ierg4210_prod_delete() {

	validate();
	// input validation or sanitization
	$_POST['pid'] = (int) $_POST['pid'];

	// DB manipulation
	global $db;
	$db = ierg4210_DB();
	$q = $db->prepare("DELETE FROM products WHERE pid = ?");
	return $q->execute(array($_POST['pid']));
}

function ierg4210_trans_fetch(){
	validate();
	
	global $db;
	$db = order_DB();
	$q = $db->prepare("SELECT * FROM orders LIMIT 50;");
	if ($q->execute())
		return $q->fetchAll();
}

header('Content-Type: application/json');

// input validation
if (empty($_REQUEST['action']) || !preg_match('/^\w+$/', $_REQUEST['action'])) {
	echo json_encode(array('failed'=>'undefined'));
	exit();
}

// The following calls the appropriate function based to the request parameter $_REQUEST['action'],
//   (e.g. When $_REQUEST['action'] is 'cat_insert', the function ierg4210_cat_insert() is called)
// the return values of the functions are then encoded in JSON format and used as output
try {
	if (($returnVal = call_user_func('ierg4210_' . $_REQUEST['action'])) === false) {
		if ($db && $db->errorCode())
			error_log(print_r($db->errorInfo(), true));
		echo json_encode(array('failed'=>'1'));
	}
	echo 'while(1);' . json_encode(array('success' => $returnVal));
} catch(PDOException $e) {
	error_log($e->getMessage(),0);
	echo json_encode(array('failed'=>'error-db'));
} catch(Exception $e) {
	echo 'while(1);' . json_encode(array('failed' => $e->getMessage()));
}
?>
