<?php
include_once('library/shiprocket.php');
include('includes/crud.php');
include('includes/custom-functions.php');
include('includes/variables.php');
$shiprocket = new Shiprocket();
$order_items = array();
$order_items[] = array(
    'name' => 'University Physics for JEE Mains and Advance | Vol 2 | Thirteenth Edition',
    'sku' => '305-295-0',
    'units' => '1',
    'selling_price' => '699'
);

$data = array(
    'order_id' => '295-49',
    'order_date' => date('y-m-d'),
    'pickup_location' => 'Primary',
    'billing_customer_name' => 'Jaya Prasad',
    'billing_last_name' => 'Jaya Prasad',
    'billing_address' => 'test 6125 612503',
    'billing_phone' => '8778624681',
    'billing_city' => 'Kumbakonam',
    'billing_pincode' => '612503',
    'billing_state' => 'Tamil Nadu',
    'billing_country' => 'India',
    'billing_email' => 'jayaprasad356@gmail.com',
    'shipping_is_billing' => true,
    "order_items" => $order_items,
    'payment_method' => 'prepaid', // change as required
    'sub_total' => '789.02',
    'length' => '5',
    'breadth' => '5',
    'height' => '5',
    'weight' => '5'
);
$credentials = $shiprocket->create_order($data);
print_r($credentials);
//echo $credentials;
?>