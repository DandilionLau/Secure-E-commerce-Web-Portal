<?php
function csrf_getNonce($action){
	// Generate a nonce with mt_rand()
	$nonce = mt_rand() . mt_rand();
	
	// With regard to $action, save the nonce in $_SESSION 
	if (!isset($_SESSION['csrf_nonce'])) 
		$_SESSION['csrf_nonce'] = array();
	$_SESSION['csrf_nonce'][$action] = $nonce;
	
	// Return the nonce
	return $nonce;
}

// Check if the nonce returned by a form matches with the stored one.
function csrf_verifyNonce($action, $receivedNonce){
	// We assume that $REQUEST['action'] is already validated
	if (isset($receivedNonce) && $_SESSION['csrf_nonce'][$action] == $receivedNonce) {
		if ($_SESSION['authtoken']==null)
			unset($_SESSION['csrf_nonce'][$action]);
		return true;
	}
	throw new Exception('csrf-attack');
}
?>