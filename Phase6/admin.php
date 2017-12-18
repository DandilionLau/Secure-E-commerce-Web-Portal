<?php
include_once('lib/csrf.php');
include_once('lib/auth.php');
include_once('lib/db.inc.php');

//session_start();
if (!auth()){
	//header('Refresh:3; login.php');
	//echo 'You are not logined <br>Redirecting you to login page in 3 second...';
	header('Location: login.php');
	exit();
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>IERG4210 Shop - Admin Panel</title>
	<link href="incl/admin.css" rel="stylesheet" type="text/css"/>
</head>

<body>
<h1>SonnaTrack - Admin Panel</h1>
<article id="main">
<section id="categoryPanel">
	<fieldset>
		<legend>New Category</legend>
		<form id="cat_insert" method="POST" action="admin-process.php?action=cat_insert" onsubmit="return false;">
			<label for="cat_insert_name">Name</label>
			<div><input id="cat_insert_name" type="text" name="name" required="true" pattern="^[\w\- ]+$" /></div>
			<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>"/>
			
			<input type="submit" value="Submit" />
		</form>

		<form id="logout" method="POST" action="auth-process.php?action=logout">
			<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>"/>

    		<input type="submit" value="Logout" />
		</form>
	</fieldset>

	<!-- Generate the existing categories here -->
	<ul id="categoryList"></ul>
</section>

<section id="categoryEditPanel" class="hide">
	<fieldset>
		<legend>Editing Category</legend>
		<form id="cat_edit" method="POST" action="admin-process.php?action=cat_edit" onsubmit="return false;">
			<label for="cat_edit_name">Name</label>

			<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>"/>

			<div><input id="cat_edit_name" type="text" name="name" required="true" pattern="^[\w\- ]+$" /></div>
			<input type="hidden" id="cat_edit_catid" name="catid" />
			<input type="submit" value="Submit" /> <input type="button" id="cat_edit_cancel" value="Cancel" />
		</form>
	</fieldset>
</section>

<section id="productPanel">
	<fieldset>
		<legend>New Product</legend>
		<form id="prod_insert" method="POST" action="admin-process.php?action=prod_insert" enctype="multipart/form-data">
			<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>"/>

			<label for="prod_insert_catid">Category *</label>
			<div><select id="prod_insert_catid" name="catid"></select></div>

			<label for="prod_insert_name">Name *</label>
			<div><input id="prod_insert_name" type="text" name="name" required="true" pattern="^[\w\- ]+$" /></div>

			<label for="prod_insert_price">Price *</label>
			<div><input id="prod_insert_price" type="number" name="price" required="true" pattern="^[\d\.]+$" /></div>

			<label for="prod_insert_description">Description</label>
			<div><textarea id="prod_insert_description" name="description" pattern="^[\w\-, ]$"></textarea></div>

			<label for="prod_insert_name">Image *</label>
			<div><input type="file" name="file" required="true" accept="image/jpeg" /></div>

			<input type="submit" value="Submit" id="prod_insert_submit"/>
		</form>
	</fieldset>



	<!-- Generate the corresponding products here -->
	<ul id="productList"></ul>

</section>

<section id="productEditPanel" class="hide">
	<!--
		Design your form for editing a product's catid, name, price, description and image
		- the original values/image should be prefilled in the relevant elements (i.e. <input>, <select>, <textarea>, <img>)
		- prompt for input errors if any, then submit the form to admin-process.php (AJAX is not required)
	-->
	<legend>Product Editing</legend>
	<form id="prod_edit" method="POST" action="admin-process.php?action=prod_edit" enctype="multipart/form-data">
		<label for="prod_edit_catid">Category *</label>
		<div><select id="prod_edit_catid" name="catid"></select></div>

		<label for="prod_edit_name">Name *</label>
		<div><input id="prod_edit_name" type="text" name="name" required="true" pattern="^[\w\- ]+$" /></div>

		<label for="prod_edit_price">Price *</label>
		<div><input id="prod_edit_price" type="number" name="price" required="true" pattern="^[\d\.]+$" /></div>

		<label for="prod_edit_description">Description</label>
		<div><textarea id="prod_edit_description" name="description" pattern="^[\w\-, ]$"></textarea></div>

		<label for="prod_edit_name">Image *</label>
		<div><input type="file" name="file" required="true" accept="image/jpeg" /></div>

		<label for="prod_edit_pid">Pid *</label>
		<div><input id="prod_edit_pid" type="number" name="pid" required="true" pattern="^[\d\.]+$" /></div>

		<input type="submit" value="Submit" id="prod_edit_submit"/> <input type="button" id="prod_edit_cancel" value="Cancel" />
	</form>
</section>

<section id="txnTable">
	<fieldset style="width:900px">
	<legend>Lastest 50 Transaction Records</legend>
		<table id = "transTable"></table>
	</fieldset>
</section>

<div class="clear"></div>
</article>

<script type="text/javascript" src="incl/myLib.js"></script>
<script type="text/javascript">

(function(){

	function updateTrans(){
		myLib.get({action:'trans_fetch'}, function(json){
				// loop over the server response json
				//   the expected format (as shown in Firebug):
				
				//orderItems.push('<tr><th width="70">Order ID</th><th width="400">Digest</th><th width="200">Salt</th><th width="200">Transaction ID</th></tr>');
				for (var orderItems = [], i = 0, order; order = json[i]; i++) {
					orderItems.push('<tr><th width="70">',parseInt(order.oid),'</th><th width="400">',order.digest.escapeHTML(),'</th><th width="200">',order.salt.escapeHTML(),'</th><th width="200">',order.tid.escapeHTML(),'</th></tr>');
				}
				el('transTable').innerHTML = orderItems.join('');
				//alert(orderItems);
			});
	}
	updateTrans();

	function updateUI() {
		myLib.get({action:'cat_fetchall'}, function(json){
			// loop over the server response json
			//   the expected format (as shown in Firebug):
			for (var options = [], listItems = [],
					i = 0, cat; cat = json[i]; i++) {
				options.push('<option value="' , parseInt(cat.catid) , '">' , cat.name.escapeHTML() , '</option>');
				listItems.push('<li id="cat' , parseInt(cat.catid) , '"><span class="name">' , cat.name.escapeHTML() , '</span> <span class="delete">[Delete]</span> <span class="edit">[Edit]</span></li>');
			}
			el('prod_insert_catid').innerHTML = '<option></option>' + options.join('');
			el('prod_edit_catid').innerHTML = '<option></option>' + options.join('');
			el('categoryList').innerHTML = listItems.join('');
		});
		el('productList').innerHTML = '';
	}
	updateUI();

	el('categoryList').onclick = function(e) {
		if (e.target.tagName != 'SPAN')
			return false;


		var target = e.target,
			parent = target.parentNode,
			id = target.parentNode.id.replace(/^cat/, ''),
			name = target.parentNode.querySelector('.name').innerHTML;

		// handle the delete click
		if ('delete' === target.className) {
			confirm('Sure?') && myLib.post({action: 'cat_delete', catid: id}, function(json){
				alert('"' + name + '" is deleted successfully!');
				updateUI();
			});

		// handle the edit click
		} else if ('edit' === target.className) {
			// toggle the edit/view display
			el('categoryEditPanel').show();
			el('categoryPanel').hide();

			// fill in the editing form with existing values
			el('cat_edit_name').value = name;
			el('cat_edit_catid').value = id;

		//handle the click on the category name
		} else {
			//el('prod_insert_catid').value = id;
			// populate the product list or navigate to admin.php?catid=<id>
			//el('productList').innerHTML = '<li> Product 1 of "' + name + '" [Edit] [Delete]</li><li> Product 2 of "' + name + '" [Edit] [Delete]</li>';
		}
	}


	el('cat_insert').onsubmit = function() {
		return myLib.submit(this, updateUI);
	}
	el('cat_edit').onsubmit = function() {
		return myLib.submit(this, function() {
			// toggle the edit/view display
			el('categoryEditPanel').hide();
			el('categoryPanel').show();
			updateUI();
		});
	}
	el('cat_edit_cancel').onclick = function() {
		// toggle the edit/view display
		el('categoryEditPanel').hide();
		el('categoryPanel').show();
	}

})();

(function(){

	function updateUI_prod() {


		myLib.get({action:'prod_fetchall'}, function(json){
			// loop over the server response json
			//   the expected format (as shown in Firebug):
			for (var options = [], listItems = [],
					i = 0, prod; prod = json[i]; i++) {
				options.push('<option value="' , parseInt(prod.catid) , '">' , prod.catid.escapeHTML() , '</option>');
				listItems.push('<li id="prod' , parseInt(prod.pid) , '"><span class="name">' , prod.name.escapeHTML() , '</span> <span class="proddelete">[Delete]</span> <span class="prodedit">[Edit]</span></li>');
			}

			el('productList').innerHTML = listItems.join('');
		});
	}
	updateUI_prod();

	el('productList').onclick = function(e) {
		if (e.target.tagName != 'SPAN')
			return false;


		var target = e.target,
			parent = target.parentNode,
			id = target.parentNode.id.replace(/^prod/, ''),
			name = target.parentNode.querySelector('.name').innerHTML;

		// handle the delete click
		if ('proddelete' === target.className) {
			confirm('Sure?') && myLib.post({action: 'prod_delete', pid: id}, function(json){
				alert('"' + name + '" is deleted successfully!');
				updateUI_prod();
			});

		// handle the edit click
	} else if ('prodedit' === target.className) {
			// toggle the edit/view display
			el('productEditPanel').show();
			el('productPanel').hide();

			// fill in the editing form with existing values
			el('prod_edit_name').value = name;
			el('prod_edit_pid').value = id;

		//handle the click on the category name
		} else {
			//el('prod_insert_catid').value = id;
			// populate the product list or navigate to admin.php?catid=<id>
			//el('productList').innerHTML = '<li> Product 1 of "' + name + '" [Edit] [Delete]</li><li> Product 2 of "' + name + '" [Edit] [Delete]</li>';
		}
	}


	el('prod_insert_submit').onsubmit = function() {
		return myLib.submit(this, updateUI_prod);
	}
	el('prod_edit_submit').onsubmit = function() {
		return myLib.submit(this, function() {
			// toggle the edit/view display
			el('productEditPanel').hide();
			el('productPanel').show();
			updateUI_prod();
		});
	}
	el('prod_edit_cancel').onclick = function() {
		// toggle the edit/view display
		el('productEditPanel').hide();
		el('productPanel').show();
	}

})();

</script>
</body>
</html>
