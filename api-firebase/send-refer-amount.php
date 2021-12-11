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


if(empty($_POST['order_id'])){
    
    $response['error'] = true;
    $response['message'] = "Order ID required";
    print_r(json_encode($response));
    return false;

}

$id = $db->escapeString($function->xss_clean($_POST['order_id']));
$sql = "SELECT id,user_id,friends_code,refer_amt_status,final_total FROM orders where id = '$id' and friends_code IS NOT NULL";
$db->sql($sql);
$res = $db->getResult();
$num = $db->numRows($res);
if ($num == 1){
    
    
    $sql = "SELECT COUNT(*) as orderitemstatus FROM order_items WHERE order_id = '$id' AND active_status = 'delivered'";
    $db->sql($sql);
    $ois = $db->getResult();
    $sql = "SELECT COUNT(*) as orderitem FROM order_items WHERE order_id = '$id'";
    $db->sql($sql);
    $oi = $db->getResult();
    $refercode= $res[0]['friends_code'];

    $sql = "SELECT id FROM users where referral_code = '$refercode'";
    $db->sql($sql);
    $resuser = $db->getResult();

    if($ois[0]['orderitemstatus'] == $oi[0]['orderitem']){
        $msg = $db->escapeString($function->xss_clean('Order Refer - Order ID : '.$_POST['order_id']));
        $user_id = $resuser[0]['id'];

        
        
        if($res[0]['refer_amt_status'] != 1){
            
            $amount = round((5 / 100) * $res[0]['final_total']);
            $balance = $function->get_wallet_balance($user_id, 'users');
            $new_balance = $balance + $amount;
            $function->update_wallet_balance($new_balance, $user_id, 'users');
            $data = array(
                'order_id' => $id,
                'user_id' => $user_id,
                'type' => 'credit',
                'amount' => $amount,
                'message' => $msg,
                'status' => 1,
            );
            $db->insert('wallet_transactions', $data);


            $data = array(
                'refer_amt_status' => 1,
            );
            $db->update('orders', $data, 'id=' . $id);
            $response['error'] = false;
            $response['message'] = "Refer Amount Send Successfully";
                
            
            print_r(json_encode($response));

        }
        else{
            $response['error'] = true;
            $response['message'] = "Sorry,Refer Amount Already Send";
            print_r(json_encode($response));

        }
        
    }
    else{
        $response['error'] = true;
        $response['message'] = "Some Order Not Delivered";
            
        
        print_r(json_encode($response));

    }

    
}
else{
    $response['error'] = true;
    $response['message'] = "No Refer Found";
        
    
    print_r(json_encode($response));

}
if(isset($_POST['refercode'])){
    $refercode = $db->escapeString($function->xss_clean($_POST['refercode']));
    $sql = "SELECT id FROM orders where friends_code = '$refercode'";
    $db->sql($sql);
    $totalorderitem = $db->getResult();
    $num = $db->numRows($totalorderitem);
    if ($num >= 1){
        foreach ($totalorderitem as $row) {
        
            $id = $row['id'];
            $sql = "SELECT COUNT(*) as orderitemstatus FROM order_items WHERE order_id = '$id' AND active_status = 'delivered'";
            $db->sql($sql);
            $ois = $db->getResult();
            $sql = "SELECT COUNT(*) as orderitem FROM order_items WHERE order_id = '$id'";
            $db->sql($sql);
            $oi = $db->getResult();
            // $tempRow['order_item_status'] = $ois[0]['orderitemstatus'];
            // $tempRow['order_item'] = $oi[0]['orderitem'];
            if($ois[0]['orderitemstatus'] == $oi[0]['orderitem']){
    
                $tempRow['sale_count'] = 1;
    
                $sql = "SELECT SUM(final_total) as total FROM orders where friends_code = '$refercode' and id = '$id' ";
                $db->sql($sql);
                $totalpurchase = $db->getResult();
                $tempRow['totalamount'] = round($totalpurchase[0]['total']);
     
                
            }
            else{
                $tempRow['sale_count'] = 0;
                $tempRow['totalamount'] = 0;
    
            }
            $rows[] = $tempRow;
        }
    
        $sum = 0;
        foreach ( $rows as $receipt )
        {
            $sum += $receipt['sale_count'];
        }
        $tr['totalsale'] = $sum;
        $tr['totalamount'] = round($tempRow['totalamount']);

        $response['error'] = false;
        $response['message'] = "Refer code Retrived successfully.";
            
        $response['data'] = $tr;
        print_r(json_encode($response));
        
    
        
        
    }
    else{
        $response['error'] = true;
        $response['message'] = "Not Found";
        
        print_r(json_encode($response));

    }
    

    // $sql = "SELECT COUNT(*) as totalsale FROM orders where friends_code = '$refercode' and active_status = 'received'";
    // $db->sql($sql);
    // $totalsale = $db->getResult();
    // $num = $db->numRows($totalsale);
    // if ($num >= 1){
    //     $sql = "SELECT SUM(final_total) as total FROM `orders` where friends_code= '$refercode' and active_status = 'received' ";
    //     $db->sql($sql);
    //     $totalpurchase = $db->getResult();

    //     $tempRow['totalsale'] = round($totalsale[0]['totalsale']);
    //     $tempRow['totalamount'] = round($totalpurchase[0]['total']);
    //     $response['error'] = false;
    //         $response['message'] = "Refer code Retrived successfully.";
            
    //     $response['data'] = $tempRow;
    //     print_r(json_encode($response));

    // }
    // else{
    //     $response['error'] = true;
    // $response['message'] = "Not Found";
    
    // print_r(json_encode($response));
    // }

    
}


?>