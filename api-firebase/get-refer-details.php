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
$function = new custom_functions();
$settings = $function->get_settings('system_timezone', true);
$app_name = $settings['app_name'];
$support_email = $settings['support_email'];
$config = $function->get_configurations();
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
        $accesskey = $db->escapeString($function->xss_clean($_POST['accesskey']));
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

if(empty($_POST['refercode'])){
    
    $response['error'] = true;
    $response['message'] = "refer code required";
    print_r(json_encode($response));
    return false;

}
if(isset($_POST['refercode'])){
    $refercode = $db->escapeString($function->xss_clean($_POST['refercode']));
    $sql = "SELECT COUNT(*) as totalsale FROM orders where friends_code = '$refercode' and active_status = 'received'";
    $db->sql($sql);
    $totalsale = $db->getResult();
    $num = $db->numRows($totalsale);
    if ($num >= 1){
        $sql = "SELECT SUM(final_total) as total FROM `orders` where friends_code= '$refercode' and active_status = 'received' ";
        $db->sql($sql);
        $totalpurchase = $db->getResult();

        $tempRow['totalsale'] = round($totalsale[0]['totalsale']);
        $tempRow['totalamount'] = round($totalpurchase[0]['total']);
        $response['error'] = false;
            $response['message'] = "Refer code Retrived successfully.";
            
        $response['data'] = $tempRow;
        print_r(json_encode($response));

    }
    else{
        $response['error'] = true;
    $response['message'] = "Not Found";
    
    print_r(json_encode($response));
    }

    
}


?>