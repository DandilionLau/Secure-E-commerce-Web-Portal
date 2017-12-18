<?php
include_once('lib/db.inc.php');
include_once('lib/auth.php');



function ierg4210_handle_checkout(){

	if (!$_SESSION['commontoken']){
		throw new Exception('Please login first.');
	}

	$list=$_REQUEST['list'];
	//throw new Exception($list);
	$list=str_replace("{", "", $list);
	$list=str_replace("}", "", $list);
	$list=str_replace("\"","", $list);
	$list_combine=str_replace(":",",", $list);
	$list_pid_qty = explode(',', $list_combine);
	$pid=array();
	$qty=array();
	for ($i=0,$j=0;$i<count($list_pid_qty);$i+=2,$j++){
		$pid[$j]=$list_pid_qty[$i];                      //retrieve pid from list
		$qty[$j]=$list_pid_qty[$i+1];                    //retrieve qty from list
	}
	
	global $db;
	$db = ierg4210_DB();
	$a = sprintf('SELECT name, price, pid FROM products WHERE pid IN (%s);',implode(',',array_fill(1, count($pid), '?'))); 

	$q = $db->prepare($a);
	if ($q->execute($pid))
		$products=$q->fetchAll(); //'products' is the array of the prices of corresponding pids
	$priceStr="";
	$totalPrice=0;
	$i=0;
	foreach($products as $pro){
		$priceStr=$priceStr.($pro["price"]*$qty[$i]).",";
		$totalPrice+=$pro["price"]*$qty[$i++];
	}
	$i=null;
	$priceStr=substr_replace($priceStr, "", strlen($priceStr)-1, 1); //elimanate the last delimiter
	$Currency="USD";
	$MerEmail="incredibleup-facilitator@gmail.com";
	$salt=mt_rand() . mt_rand();
	$digest=sha1($Currency. $MerEmail. $salt. $list_combine.'|'. $priceStr.'|'. $totalPrice); //generate rhe digest
	//$digest=sha1($Currency. $MerEmail. $salt. $list_combine. $priceStr);
		//throw new Exception($priceStr.'---'.$totalPrice);

	$db = order_DB();
	$q = $db->prepare("INSERT INTO orders (digest, salt, tid) VALUES (?, ?, ?)");
	$q->execute(array($digest, $salt, "notyet")); //insert digest
	$invoice=$db->lastInsertId();

	//throw new Exception($invoice);
	$returnValue=array("digest"=>$digest, "invoice"=>$invoice);
	return $returnValue;
}

//function should be above
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
	error_log($e->getMessage());
	echo json_encode(array('failed'=>'error-db'));
} catch(Exception $e) {
	echo 'while(1);' . json_encode(array('failed' => $e->getMessage()));
}
?>