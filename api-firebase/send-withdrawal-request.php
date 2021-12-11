<?php
header('Access-Control-Allow-Origin: *');
include_once('send-email.php');
include_once('../includes/crud.php');
include_once('../includes/custom-functions.php');
include_once('../includes/variables.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES utf8");
$fn = new custom_functions();
$settings = $fn->get_settings('system_timezone', true);
$app_name = $settings['app_name'];
$support_email = $settings['support_email'];
$config = $fn->get_configurations();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}
if (isset($_POST['ajaxCall']) && !empty($_POST['ajaxCall'])) {
    $accesskey = "90336";
    $cancel_order_from = "admin";
} else {
    if (isset($_POST['accesskey']) && !empty($_POST['accesskey'])) {
        $accesskey = $db->escapeString($fn->xss_clean($_POST['accesskey']));
    } else {
        $response['error'] = true;
        $response['message'] = "accesskey required";
        print_r(json_encode($response));
        return false;
    }
}

if ($access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey";
    print_r(json_encode($response));
    return false;
}
if(empty($_POST['amount'])){
    
    $response['error'] = true;
    $response['message'] = "amount required";
    print_r(json_encode($response));
    return false;

}
if(empty($_POST['type'])){
    
    $response['error'] = true;
    $response['message'] = "type required";
    print_r(json_encode($response));
    return false;

}
if(empty($_POST['type_id'])){
    
    $response['error'] = true;
    $response['message'] = "type id required";
    print_r(json_encode($response));
    return false;

}
if (isset($_POST['send_request']) && $_POST['send_request'] == 1) {
    /*
    20.send_request
        accesskey:90336
        send_request:1
        type:seller
        type_id:3
        amount:1000
        message:Message {optional}
    */
    $res_msg = "";
    $res_msg .= (empty($_POST['type']) || $_POST['type'] == "") ? "type," : "";
    $res_msg .= (empty($_POST['type_id']) || $_POST['type_id'] == "") ? "type_id," : "";
    if ($res_msg != "") {
        $response['error'] = true;
        $response['message'] = "this fields " . trim($res_msg, ",") . " should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    // if (empty($_POST['type']) || empty($_POST['type_id']) || empty($_POST['amount']) ) {
    //     $response['error'] = true;
    //     $response['message'] = "All fields should be Passed!";
    //     print_r(json_encode($response));
    //     return false;
    //     exit();
    // }
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $type_id = $db->escapeString($fn->xss_clean($_POST['type_id']));
    $amount  = $db->escapeString($fn->xss_clean($_POST['amount']));
    $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
    $order_item_id = (isset($_POST['order_item_id']) && !empty($_POST['order_item_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_item_id'])) : "";
    $message = (isset($_POST['message']) && !empty($_POST['message'])) ? $db->escapeString($fn->xss_clean($_POST['message'])) : "";
    // $type1 = $type == 'user' ? 'user' : 'delivery boy';
    if (!empty($type) && !empty($type_id) && !empty($amount)) {
        // check if such user or delivery boy exists or not
        if ($fn->is_user_exists($type, $type_id)) {
            // checking if balance is greater than amount requested or not 
            $balance = $fn->get_user_balance($type, $type_id);
            if ($balance >= $amount) {
                // Debit amount requeted
                $new_balance =  $balance - $amount;
                if ($fn->debit_user_balance($type, $type_id, $new_balance)) {
                    // store wallet transaction
                    if ($type == 'user') {
                        
                        $fn->add_wallet_transaction($order_id, $order_item_id, $type_id, 'debit', $amount, $message, 'wallet_transactions');
                    }
                    // store withdrawal request
                    if ($fn->store_withdrawal_request($type, $type_id, $amount, $message)) {
                        $sql = "select balance from users where id=$type_id";
                        $db->sql($sql);
                        $res = $db->getResult();
                        $response['error'] = false;
                        $response['message'] = 'Withdrawal request accepted successfully!please wait for confirmation.';
                        $response['updated_balance'] = $res[0]['balance'];
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Something went wrong please try again later!';
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Something went wrong please try again later!';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Insufficient balance';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such ' . $type . ' exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}


?>