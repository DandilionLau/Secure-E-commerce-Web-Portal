<?php
include_once('lib/csrf.php');
include_once('lib/auth.php');
//session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>MainPage</title>
	<link href="incl/admin.css" rel="stylesheet" type="text/css"/>
</head>
<body>
	<h1>
		<p class="center">
			<i>SonnaTrack Store</i>
		</p>
	</h1>

	<div class="mlist">
		<p><a href="index.php">Home</a></p>
	</div>

	<form id="logout" method="POST" action="auth-process.php?action=logout" >
    		 <input type="submit" value="Logout" />
	</form>

	<form id="login" method="POST" action="auth-process.php?action=turnin" >
    		 <input type="submit" value="Login" />
	</form>

	<div id="shoppingCart" class="slist">
				<nav><p id="price"></p>
					<ul id="cartPanel"></ul>
					<form method="POST" action="https://www.sandbox.paypal.com/cgi-bin/webscr" onsubmit="return cartSubmit(this);">
						<ul id="submitPanel"></ul>
						<input type="hidden" name="cmd" value="_cart" />
						<input type="hidden" name="upload" value="1" />
						<input type="hidden" name="business" value="incredibleup-facilitator@gmail.com" />
						<input type="hidden" name="currency_code" value="USD" />
						<input type="hidden" name="charset" value="utf-8" />
						<input type="hidden" name="custom" value="0" />
						<input type="hidden" name="invoice" value="0" />
						<input type="submit" value="Checkout" />
					</form>
				</nav>
	</div>

<section id="categoryListPanel">
	<div class="clist">
		<p class="ct"><i>Categories:</i></p>
		<ul id="categoryList"></ul>
	</div>
<section id="categoryListPanel">

<section id="categoryPanel">
	<div class="plist">
		<ul class="table" id="productList"></ul>
	</div>
</section>

<section id="productPanel">
	<div class="pic" id="productImage">
	</div>
	<div class="info" id="productInfo">
	</div>
</section>

<script type="text/javascript" src="incl/myLib.js"></script>
<script type="text/javascript">

function Click(id,option)
{
	if(localStorage.getItem(id) == null)
	{
		localStorage.setItem(id, JSON.stringify(0));
	} 

	if(option == 1)
	{
		var number = JSON.parse(localStorage.getItem(id)) + 1;
		localStorage.setItem(id, JSON.stringify(number));
	}
	else if(option == 2)
	{
		var number = JSON.parse(localStorage.getItem(id)) - 1;
		if(number == 0)
		{
			localStorage.removeItem(id);
		}
		else
		{
			localStorage.setItem(id, JSON.stringify(number));
		}
	}
	else if(option == 3)
	{
		localStorage.removeItem(id);
	}

	totalPrice = 0;

	myLib.get({action:'prod_fetchall'}, function(json){
		for (var listItems = [],
				i = 0, prod; prod = json[i]; i++) {
			if(localStorage.getItem(parseInt(prod.pid)) === null){}
			else{
				listItems.push('<li id="prod' , parseInt(prod.pid) ,'">' ,prod.name.escapeHTML() ,'  Price:  $', prod.price.escapeHTML(),'  Quantity:  ', localStorage.getItem(parseInt(prod.pid)),'   ',
						'<button id="IncreaseButton" onclick="Click(',parseInt(prod.pid),',',parseInt(1),')">+</button><button id="decreaseButton" onclick="Click(',parseInt(prod.pid),',',parseInt(2),')">-</button><button id="decreaseButton" onclick="Click(',parseInt(prod.pid),',',parseInt(3),')">x</button></li>');
			}
			totalPrice += localStorage.getItem(parseInt(prod.pid)) * prod.price;
		}
		el('cartPanel').innerHTML = listItems.join('');

		ListItems3 = [];
		ListItems3.push('Shopping List: $',parseInt(totalPrice));
		el('price').innerHTML = ListItems3.join('');
	});
}

function cartSubmit(form){
	var buyList = {};

	for (var key in localStorage){
		buyList[key] = parseInt(localStorage.getItem(key));
	}

	updateCart();
	myLib.processJSON(
		    "checkout-process.php",                                      //para 1
		    {action: "handle_checkout", list:JSON.stringify(buyList)},   //para 2
		    function(returnValue){                                       //para 3
				form.custom.value=returnValue.digest;
				form.invoice.value=returnValue.invoice;
				//alert(form.custom.value);
				form.submit();
				for (var key in localStorage)                    //remove local storage
					localStorage.removeItem(key);
			},
		    {method:"POST"});                                            //para 4
	return false;
}

function ClickProd(id)
{
	myLib.get({action:'prod_select',pid: id}, function(json){
				// loop over the server response json
				//   the expected format (as shown in Firebug): 
				for (var listItems1 = [], listItems2 = [],
						i = 0, prod; prod = json[i]; i++) {

					listItems1.push('<img src="incl/img/', parseInt(prod.pid), '.jpg"/>');
					listItems2.push('<li id="prod', parseInt(prod.pid),'">',prod.name.escapeHTML(),'</li>');
					listItems2.push('<li id="prod', parseInt(prod.pid),'">','$',prod.price.escapeHTML(),'</li>');
					listItems2.push('<li id="prod', parseInt(prod.pid),'">',prod.description.escapeHTML(),'</li>');
					listItems2.push('<button type="button" onclick="Click(',parseInt(prod.pid),',',parseInt(1),')">Add To Cart!</button>')
					}

				el('productImage').innerHTML = listItems1.join('');
				el('productInfo').innerHTML = listItems2.join('');

			});
			// fill in the editing form with existing values

			el('productPanel').show();
			//el('categoryListPanel').show();
			el('categoryPanel').hide();
			updateCat();
}

function ClickCat(id)
{
	myLib.get({action:'cat_select',catid: id}, function(json){
		// loop over the server response json
		//   the expected format (as shown in Firebug): 
		for (var listItems = [],
				i = 0, prod; prod = json[i]; i++) {

				listItems.push('<li id="prod' , parseInt(prod.pid) , '">' , '<img src="incl/img/', parseInt(prod.pid), '.jpg"/ onclick="ClickProd(',parseInt(prod.pid),')">', '<p class="center" >',prod.name.escapeHTML() , ' - $', prod.price.escapeHTML(),'</p><p class="center"><button type="button" onclick="Click(',parseInt(prod.pid),',',parseInt(1),')">Add To Cart!</button></p></li>');
					}
				el('productList').innerHTML = listItems.join('');
		});
	updateCat();
}

function updateCat()
{
	myLib.get({action:'cat_fetchall'}, function(json){
			// loop over the server response json
			//   the expected format (as shown in Firebug): 
			for (var listItems = [],
					i = 0, cat; cat = json[i]; i++) {
				listItems.push('<li id="cat' , parseInt(cat.catid) , '" onclick="ClickCat(',parseInt(cat.catid),')"><i>' , cat.name.escapeHTML() , '</i></li>');
			}
			el('categoryList').innerHTML = listItems.join('');
		});
}

function updateCart() {
		totalPrice = 0;

		myLib.get({action:'prod_fetchall'}, function(json){
			for (var listItems = [],
					i = 0, prod; prod = json[i]; i++) {
				if(localStorage.getItem(parseInt(prod.pid)) === null){}
				else{
					listItems.push('<li id="prod' , parseInt(prod.pid) ,'">' ,prod.name.escapeHTML() ,'  Price:  $', prod.price.escapeHTML(),'  Quantity:  ', localStorage.getItem(parseInt(prod.pid)),'   ',
							'<button id="IncreaseButton" onclick="Click(',parseInt(prod.pid),',',parseInt(1),')">+</button><button id="decreaseButton" onclick="Click(',parseInt(prod.pid),',',parseInt(2),')">-</button><button id="decreaseButton" onclick="Click(',parseInt(prod.pid),',',parseInt(3),')">x</button></li>');
				}
				totalPrice += localStorage.getItem(parseInt(prod.pid)) * prod.price;
			}
			el('cartPanel').innerHTML = listItems.join('');
			//alert(listItems);

			ListItems3 = [];
			ListItems3.push('Shopping List: $',parseInt(totalPrice));
			el('price').innerHTML = ListItems3.join('');

			count = 0;
			for (var prodItems = [],
					i = 0, prod; prod = json[i]; i++) {
				if(localStorage.getItem(parseInt(prod.pid)) === null){}
				else{
					count += 1;
					prodItems.push('<input type="hidden" name="item_name_',parseInt(count),'" value="'+prod.name.escapeHTML()+'"/>');
					prodItems.push('<input type="hidden" name="item_number_',parseInt(count),'" value="'+prod.pid.escapeHTML()+'"/>');
					prodItems.push('<input type="hidden" name="quantity_',parseInt(count),'" value="'+localStorage.getItem(parseInt(prod.pid))+'"/>');
					prodItems.push('<input type="hidden" name="amount_',parseInt(count),'" value="'+parseFloat(prod.price)+'"/>');
				}
			}
			//alert(prodItems);
			el('submitPanel').innerHTML = prodItems.join('');
		});
}

(function(){

	function updateUI() {
		
		myLib.get({action:'prod_fetchlimit'}, function(json){
			// loop over the server response json
			//   the expected format (as shown in Firebug): 
			for (var listItems = [],
					i = 0, prod; prod = json[i]; i++) {
				listItems.push('<li id="prod' , parseInt(prod.pid) , '">' , '<img src="incl/img/', parseInt(prod.pid), '.jpg"/ onclick="ClickProd(',parseInt(prod.pid),')">', '<p class="center" onclick="ClickProd(',parseInt(prod.pid),')">',prod.name.escapeHTML() , ' - $', prod.price.escapeHTML(),'</p><p class="center"><button type="button" onclick="Click(',parseInt(prod.pid),',',parseInt(1),')">Add To Cart!</button></p></li>');
			}
			el('productList').innerHTML = listItems.join('');
		});
		updateCart();
		updateCat();
	}
	updateUI();

})();
</script>
</body>
</html>

<style>
	nav ul{display: none}
	nav:hover ul{display: block}
	p.right{text-align: right;}
	p.center{text-align: center;font-size:75%;line-height: 75%}
	p.ct{font-size: 150%;}

	div.mlist{position: absolute; top: 20%; height: 10%; left: 15%; width:85%;}
	div.plist{position: absolute; top: 30%; height: 100%; left:15%;width:85%;}
	div.slist{position: absolute; top: 5%; height: 15%; left:75%; width:25%;font-size: 90%;line-height: 75%;}
	div.clist{position: absolute; top: 25%; height: 30%; left:0%; width:30%;}
	div.pic{position: absolute;top: 30%; height: 60%; left: 12%; width: 50%;}
	div.info{position: absolute;top: 45%; height: 60%; left: 50%; width: 50%;}

	div.product{display:none}
	div.display_area{display: block}

	
	ul.table{width:100%;height:100%;margin:0;padding:0;list-style:none;overflow:auto}
	ul.table li{width:30%;height:50%;float:left;border:0.1px solid #CCC;overflow: auto}
	

	/*
	ul.table{position: absolute; top:30%; left: 15%;width: 85%;height: 70%;margin: 0;padding: 0;list-style: none;overflow: auto}
	ul.table li{width:250px;height:300px;float:left;border:0.1px solid #CCC;overflow: auto}
	.clear{clear: both}
	*/

	img{max-width: 90%; max-height: 90%; padding-left:5%; padding-right:5%}
</style>


