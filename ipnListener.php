<?php
include_once('lib/db.inc.php');
//error_log("hey");
//error_log(print_r($_POST, true));
error_reporting(E_ALL ^ E_NOTICE);
$email = $_GET['ipn_email'];
$header = "";
$emailtext = "";
// Read the post from PayPal and add 'cmd' 
$req = 'cmd=_notify-validate'; 
if(function_exists('get_magic_quotes_gpc')) 
{ 
	$get_magic_quotes_exists = true; 
} 
foreach ($_POST as $key => $value) 
// Handle escape characters, which depends on setting of magic quotes
{ if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1){
		$value = urlencode(stripslashes($value)); 
	}
	else { 
		$value = urlencode($value); 
	} 
	$req .= "&$key=$value";
}
 // Post back to PayPal to validate 
$header .= "POST /cgi-bin/webscr HTTP/1.1\r\n";
$header .= "Host: www.paypal.com\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n"; 
$header .= "Content-Length: " . strlen($req) . "\r\n";
$header .= "Connection: close\r\n\r\n";
$fp = fsockopen('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
 
 // Process validation from PayPal
 // TODO: This sample does not test the HTTP response code. All 
 // HTTP response codes must be handles or you should use an HTTP library, such as cUrl 
 if (!$fp) { // HTTP ERROR 
 } else { // NO HTTP ERROR 
 fputs ($fp, $header . $req); 
 while (!feof($fp)) {
	$res = fgets ($fp, 1024);
	if (strcmp ($res, "VERIFIED\r\n") == 0) { 
		// TODO: Check the payment_status is Completed
		error_log($_POST['payment_status']);
		if (empty($_POST['payment_status'])||$_POST['payment_status']!='Completed')
		{
			error_log("payment is not completed");
			break;
		}
		// Check that txn_id has not been previously processed
		global $db;
		$db = order_DB();
	    $q = $db->prepare("SELECT * FROM orders LIMIT 100;");
		if ($q->execute()){
	         $cartOrder=$q->fetchAll();}
		$invoice=$_POST['invoice'];
		error_log("test invoice: ".$invoice);
		foreach($cartOrder as $car){
			if ($car['oid']==$invoice){
				if($car['tid']==$_POST['txn_id']){
				     error_log("Duplicate Traction!!!");
					 break;
				}
			}
		}
		// Check that receiver_email is your Primary PayPal email
		$email="incredibleup-facilitator@gmail.com";
		if($_POST['receiver_email']==$email){
			error_log("correct email");
		}else{
			error_log("incorect email");
			break;
		}
		// Check that payment_amount/payment_currency are correct
    	$q = $db->prepare("SELECT * FROM orders WHERE oid = ?");
    	if ($q->execute(array($_POST['invoice'])))
			$return_order=$q->fetchAll();
		$digestOld=$return_order[0]["digest"];
		$salt=$return_order[0]["salt"];
		error_log('salt:'.$return_order[0]["salt"]);
		
		$i=1;
		$list=array();
		$pidList=array();
		$qtyList=array();
		$priceList=array();
		do{
			error_log('the i='.$i);
			array_push($list, (int)($_POST['item_number'.$i]), (int)($_POST['quantity'.$i]));
			array_push($pidList, (int)($_POST['item_number'.$i]));
			array_push($qtyList, (int)($_POST['quantity'.$i]));
			array_push($priceList, (float)($_POST['mc_gross_'.$i]));
			error_log('prod pid='.(int)($_POST['item_number'.$i]).' its qty='.(int)($_POST['quantity'.$i]).' its $='.(float)($_POST['mc_gross_'.$i]));
		}while ($_POST['item_number'.++$i]);
		$list_combine=implode(",", $list);
		$pidList_combine=implode(",", $pidList);
		$qtyList_combine=implode(",", $qtyList);
		$priceList_combine=implode(",", $priceList);
		error_log('list_combine:'.$list_combine.' pids='.$pidList_combine.' qtys='.$qtyList_combine.' price='.$priceList_combine);
		$Currency=$_POST['mc_currency'];
	    $MerEmail=$_POST['business'];
		
		$concat = ($Currency. $MerEmail. $salt. $list_combine.'|'. $priceStr.'|'. $totalPrice);

		$priceStr=$priceList_combine;
		$totalPrice=(float)($_POST['mc_gross']);
		error_log('priceStr='.$priceStr.' ttprice='.$totalPrice);
		$concat = $_POST['txn_id'];
		$digest=sha1($Currency. $MerEmail. $salt. $list_combine.'|'. $priceStr.'|'. $totalPrice);
		//$digest=sha1($Currency. $MerEmail. $salt. $list_combine. $priceStr);

        if ($digest==$digestOld)
		{
			$q = $db->prepare("UPDATE orders SET tid = ? WHERE oid = ?");
	        $q->execute(array($_POST['txn_id'], $_POST['invoice']));
			error_log('traction ID:'.$_POST['txn_id']);
		}else {
			$q = $db->prepare("UPDATE orders SET tid = ? WHERE oid = ?");
	        $q->execute(array($concat, $_POST['invoice']));
			error_log('digest_not_match!!!');
		}
		// If 'VERIFIED', send email of IPN variables and values to specified email address 
		foreach ($_POST as $key => $value){ 
			$emailtext .= $key . " = " .$value ."\n\n";
		}
		error_log($email.'Live-VERIFIED IPN'.$emailtext .'\n\n'.$req);
		exit();
	} 
	else if (strcmp ($res, "INVALID") == 0) { 
		// If 'INVALID', send an email. TODO: Log for manual investigation. 
		foreach ($_POST as $key => $value){ 
			$emailtext .= $key . " = " .$value ."\n\n"; 
			} 
			error_log($email.'Live-INVALID IPN'.$emailtext.'\n\n'.$req);
			exit();
		} 
	} fclose ($fp);
}
?>