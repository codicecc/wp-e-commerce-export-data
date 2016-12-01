<?php

/*

1612011230 - Francesco Dattolo
The script creates a csv file.
It extracts the order data from wordpress wp-e-commerce plugin.

Tables involved:
1.	wp_wpsc_checkout_forms
2.	wp_wpsc_submited_form_data
3.	wp_wpsc_purchase_logs

*/

$mysqli = new mysqli('localhost', 'username', 'password', 'nome_database');

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
 
$query="
SELECT 
	log_id,
	(SELECT name from wp_wpsc_checkout_forms where id=form_id) as name,
	(SELECT unique_name from wp_wpsc_checkout_forms where id=form_id) as unique_name,
	value
FROM 
	`wp_wpsc_submited_form_data` 
WHERE 
	log_id IN (SELECT log_id FROM `wp_wpsc_submited_form_data` WHERE 1 group BY log_id)
ORDER BY log_id, form_id
";

// Get log_id ARRAY
$alog_id=array();
$query="SELECT distinct log_id FROM `wp_wpsc_submited_form_data` WHERE 1 ORDER BY log_id";
if ($result = $mysqli->query($query)) {
	while ($obj = $result->fetch_object()) {
		array_push($alog_id,$obj->log_id);
		//printf("%s\n",$obj->log_id);
	}
	$result->close();
}

// Get row data ARRAY
$arow_data=array();

// Initialize data sheet
$aall_row_data=array(array(
		'billingfirstname',
		'billinglastname',
		'billingaddress',
		'billingcity',
		'billingstate',
		'billingcountry',
		'billingpostcode',
		'billingemail',
		'shippingfirstname',
		'shippinglastname',
		'shippingaddress',
		'shippingcity',
		'shippingstate',
		'shippingcountry',
		'shippingpostcode',
		'billingphone',
		'codice-fiscale',
		'richiedi-fattura',
		'prodotto',
		'totalprice',
		'data',
		'gateway')
		);

// Check here the initial row
for($i=2;$i<count($alog_id);$i++){
	$query="
SELECT 
	value,
	(SELECT totalprice FROM `wp_wpsc_purchase_logs` where id=".$alog_id[$i].") as totalprice,
	(SELECT gateway FROM `wp_wpsc_purchase_logs` where id=".$alog_id[$i].") as gateway,
	(SELECT date FROM `wp_wpsc_purchase_logs` where id=".$alog_id[$i].") as date,
	(SELECT name FROM `wp_wpsc_cart_contents` where purchaseid IN (SELECT id FROM `wp_wpsc_purchase_logs` where id=".$alog_id[$i].")) as productname
FROM `wp_wpsc_submited_form_data` WHERE log_id=".$alog_id[$i]."
ORDER BY log_id, form_id
	";
	$arow_data=array();
	$totalprice="";
	$date="";
	$gateway="";
	$productname="";
	if ($result = $mysqli->query($query)) {
		while ($obj = $result->fetch_object()) {
			array_push($arow_data,$obj->value);
			if(empty($totalprice))$totalprice=$obj->totalprice;
			if(empty($date))$date=date("Y-m-d H:i:s",$obj->date);
			if(empty($gateway))$gateway=$obj->gateway;
			if(empty($productname))$productname=$obj->productname;
		}
		array_push($arow_data,$productname,$totalprice,$date,$gateway);
		array_push($aall_row_data,$arow_data);
	}
}

$result->close();


/* close connection */
$mysqli->close();

$list = array (
  $aall_row_data
);
$list=$aall_row_data;

$fp = fopen('file.csv', 'w');

foreach ($list as $fields) {
    fputcsv($fp, $fields);
}

fclose($fp);
?>
