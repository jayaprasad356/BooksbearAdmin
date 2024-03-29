<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
include '../includes/crud.php';
require_once '../includes/functions.php';
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
include_once('../includes/variables.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

/* 
-------------------------------------------
APIs for Multi Vendor
-------------------------------------------
1. add_address
2. update_address
3. delete_address
4. get_addresses
-------------------------------------------
-------------------------------------------
*/

if (!verify_token()) {
    return false;
}


if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['add_address'])) && ($_POST['add_address'] == 1)) {
    /*
    1.add_address
        accesskey:90336
        add_address:1
        user_id:3
        name:abc
        mobile:1234567890
        type:Home/Office
        address:Time Square Empire
        landmark:Bhuj-Mirzapar Highway
        area_id:1
        pincode_id:2
        city_id:2
        state:Gujarat
        country:India
        alternate_mobile:9876543210 // {optional}
        country_code:+91            // {optional}
        latitude:value              // {optional}
        longitude:value             // {optional}
        is_default:0/1              // {optional}
    */

    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $type = (isset($_POST['type']) && !empty($_POST['type'])) ? $db->escapeString($fn->xss_clean($_POST['type'])) : "";
    $name = (isset($_POST['name']) && !empty($_POST['name'])) ? $db->escapeString($fn->xss_clean($_POST['name'])) : "";
    $mobile = (isset($_POST['mobile']) && !empty($_POST['mobile'])) ? $db->escapeString($fn->xss_clean($_POST['mobile'])) : "";
    $country_code  = (isset($_POST['country_code']) && !empty($_POST['country_code'])) ? $db->escapeString($fn->xss_clean($_POST['country_code'])) : "";
    $alternate_mobile = (isset($_POST['alternate_mobile']) && !empty($_POST['alternate_mobile'])) ? $db->escapeString($fn->xss_clean($_POST['alternate_mobile'])) : "";
    $address =  (isset($_POST['address']) && !empty($_POST['address'])) ? $db->escapeString($fn->xss_clean($_POST['address'])) : "";
    $landmark = (isset($_POST['landmark']) && !empty($_POST['landmark'])) ? $db->escapeString($fn->xss_clean($_POST['landmark'])) : "";
    $area_id = (isset($_POST['area_id']) && !empty($_POST['area_id'])) ? $db->escapeString($fn->xss_clean($_POST['area_id'])) : "";
    $pincode_id = (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "";
    $city_id = (isset($_POST['city_id']) && !empty($_POST['city_id'])) ? $db->escapeString($fn->xss_clean($_POST['city_id'])) : "";
    $pincode = (isset($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean($_POST['pincode'])) : "";
    $city = (isset($_POST['city']) && !empty($_POST['city'])) ? $db->escapeString($fn->xss_clean($_POST['city'])) : "";
    $area = (isset($_POST['area']) && !empty($_POST['area'])) ? $db->escapeString($fn->xss_clean($_POST['area'])) : "";
    $state = (isset($_POST['state']) && !empty($_POST['state'])) ? $db->escapeString($fn->xss_clean($_POST['state'])) : "";
    $country = (isset($_POST['country']) && !empty($_POST['country'])) ? $db->escapeString($fn->xss_clean($_POST['country'])) : "";
    $latitude = (isset($_POST['latitude']) && !empty($_POST['latitude'])) ? $db->escapeString($fn->xss_clean($_POST['latitude'])) : "0";
    $longitude = (isset($_POST['longitude']) && !empty($_POST['longitude'])) ? $db->escapeString($fn->xss_clean($_POST['longitude'])) : "0";
    $is_default = (isset($_POST['is_default']) && !empty($_POST['is_default'])) ? $db->escapeString($fn->xss_clean($_POST['is_default'])) : "0";

    if (!empty($user_id) && !empty($type) && !empty($name) && !empty($mobile) && !empty($address) && !empty($landmark) && !empty($area_id) && !empty($pincode_id) && !empty($city_id) && !empty($state) && !empty($country) && !empty($city) && !empty($area) && !empty($pincode)) {
        if ($is_default == 1) {
            $fn->remove_other_addresses_from_default($user_id);
        }
        $data = array(
            'user_id' => $user_id,
            'type' => $type,
            'name' => $name,
            'mobile' => $mobile,
            'alternate_mobile' => $alternate_mobile,
            'address' => $address,
            'landmark' => $landmark,
            'pincode' => $pincode,
            'area' => $area,
            'city' => $city,
            'area_id' => $area_id,
            'pincode_id' => $pincode_id,
            'city_id' => $city_id,
            'state' => $state,
            'country' => $country,
            'latitude' => $latitude == "" ? "0" : $latitude,
            'longitude' => $longitude == "" ? "0" : $longitude,
            'is_default' => $is_default,
        );
        $db->insert('user_addresses', $data);
        $res_insert = $db->getResult();
        if (!empty($res_insert)) {
            $d_charges = $fn->get_data($columns = ['minimum_free_delivery_order_amount', 'delivery_charges'], 'id=' . $area_id, 'area');
            $res = $db->getResult();

            $sql = "select ua.*,u.name as user_name,u.mobile as mobile,a.name as area_name,p.pincode as pincode,c.name as city,a.minimum_free_delivery_order_amount as minimum_free_delivery_order_amount,a.delivery_charges as delivery_charges from user_addresses ua LEFT JOIN area a ON a.id=ua.area_id LEFT JOIN pincodes p ON p.id=ua.pincode_id LEFT JOIN users u ON u.id=ua.user_id LEFT JOIN cities c on c.id=a.city_id  where ua.user_id= $user_id and ua.id=" . $res_insert[0] . " ORDER BY is_default DESC";
            $db->sql($sql);
            $res1 = $db->getResult();

            $response['error'] = false;
            $response['message'] = 'Address added successfully';
            $response["id"] = strval($res_insert[0]);
            $response['user_id'] = $user_id;
            $response['type'] = $type;
            $response['name'] = $name;
            $response['mobile'] = $mobile;
            $response['country_code'] = $country_code;
            $response['alternate_mobile'] = $alternate_mobile;
            $response['address'] = $address;
            $response['landmark'] = $landmark;
            $response['area_id'] = $area_id;
            $response['area_name'] = !empty($res1[0]['area_name']) ? $res1[0]['area_name'] : "";
            $response['pincode_id'] = $pincode_id;
            $response['pincode'] = !empty($res1[0]['pincode']) ? $res1[0]['pincode'] : "";
            $response['city'] = !empty($res1[0]['city']) ? $res1[0]['city'] : "";
            $response['state'] = $state;
            $response['country'] = $country;
            $response['latitude'] = $latitude == "" ? "0" : $latitude;
            $response['longitude'] = $longitude == "" ? "0" : $longitude;
            $response['is_default'] = $is_default == "" ? "0" : $is_default;
            $response['minimum_free_delivery_order_amount'] = (!empty($d_charges[0]['minimum_free_delivery_order_amount'])) ? $d_charges[0]['minimum_free_delivery_order_amount'] : "0";
            $response['delivery_charges'] = (!empty($d_charges[0]['delivery_charges'])) ? $d_charges[0]['delivery_charges'] : "0";
        } else {
            $response['error'] = true;
            $response['message'] = 'Something went wrong please try again!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['update_address'])) && ($_POST['update_address'] == 1)) {
    /*
    2.update_address
        accesskey:90336
        update_address:1
        id:1
        user_id:1
        is_default:0/1
        name:1                          // {optional}
        type:Home/Office                // {optional}
        mobile:9876543210                // {optional}
        alternate_mobile:9876543210     // {optional}
        address:Time Square Empire      // {optional}
        landmark:Bhuj-Mirzapar Highway  // {optional}
        area_id:1                       // {optional}
        pincode_id:2                    // {optional}
        city_id:2                       // {optional}
        state:Gujarat                   // {optional}
        country:India                   // {optional}
        latitude:value                  // {optional}
        longitude:value                 // {optional}
        
    */

    $id = (isset($_POST['id']) && !empty($_POST['id'])) ? trim($db->escapeString($fn->xss_clean($_POST['id']))) : "";
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? trim($db->escapeString($fn->xss_clean($_POST['user_id']))) : "";
    $name = (isset($_POST['name']) && !empty($_POST['name'])) ? trim($db->escapeString($fn->xss_clean($_POST['name']))) : "";
    $mobile = (isset($_POST['mobile']) && !empty($_POST['mobile'])) ? trim($db->escapeString($fn->xss_clean($_POST['mobile']))) : "";
    $type = (isset($_POST['type']) && !empty($_POST['type'])) ? trim($db->escapeString($fn->xss_clean($_POST['type']))) : "";
    $alternate_mobile = (isset($_POST['alternate_mobile']) && !empty($_POST['alternate_mobile'])) ? trim($db->escapeString($fn->xss_clean($_POST['alternate_mobile']))) : "";
    $address = (isset($_POST['address']) && !empty($_POST['address'])) ? trim($db->escapeString($fn->xss_clean($_POST['address']))) : "";
    $landmark = (isset($_POST['landmark']) && !empty($_POST['landmark'])) ? trim($db->escapeString($fn->xss_clean($_POST['landmark']))) : "";
    $area_id = (isset($_POST['area_id']) && !empty($_POST['area_id'])) ? trim($db->escapeString($fn->xss_clean($_POST['area_id']))) : "";
    $pincode_id = (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? trim($db->escapeString($fn->xss_clean($_POST['pincode_id']))) : "";
    $city_id = (isset($_POST['city_id']) && !empty($_POST['city_id'])) ? $db->escapeString($fn->xss_clean($_POST['city_id'])) : "";
    $pincode = (isset($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean($_POST['pincode'])) : "";
    $city = (isset($_POST['city']) && !empty($_POST['city'])) ? $db->escapeString($fn->xss_clean($_POST['city'])) : "";
    $area = (isset($_POST['area']) && !empty($_POST['area'])) ? $db->escapeString($fn->xss_clean($_POST['area'])) : "";
    $state = (isset($_POST['state']) && !empty($_POST['state'])) ? trim($db->escapeString($fn->xss_clean($_POST['state']))) : "";
    $country = (isset($_POST['country']) && !empty($_POST['country'])) ? trim($db->escapeString($fn->xss_clean($_POST['country']))) : "";
    $latitude = (isset($_POST['latitude']) && !empty($_POST['latitude'])) ? trim($db->escapeString($fn->xss_clean($_POST['latitude']))) : "0";
    $longitude = (isset($_POST['longitude']) && !empty($_POST['longitude'])) ? trim($db->escapeString($fn->xss_clean($_POST['longitude']))) : "0";
    $is_default = (isset($_POST['is_default']) && !empty($_POST['is_default'])) ? trim($db->escapeString($fn->xss_clean($_POST['is_default']))) : "";

    if (!empty($id) && !empty($user_id)) {
        if ($is_default == 1) {
            $fn->remove_other_addresses_from_default($user_id);
        }

        if ($fn->is_address_exists($id)) {
            $data = array(
                'type' => $type,
                'alternate_mobile' => $alternate_mobile,
                'mobile' => $mobile,
                'name' => $name,
                'address' => $address,
                'landmark' => $landmark,
                'pincode' => $pincode,
                'area' => $area,
                'city' => $city,
                'area_id' => $area_id,
                'pincode_id' => $pincode_id,
                'city_id' => $city_id,
                'state' => $state,
                'country' => $country,
                'latitude' => $latitude == "" ? "0" : $latitude,
                'longitude' => $longitude == "" ? "0" : $longitude,
                'is_default' => $is_default
            );

            if ($db->update('user_addresses', $data, 'id=' . $id)) {
                $d_charges = $fn->get_data($columns = ['minimum_free_delivery_order_amount', 'delivery_charges', 'name', 'pincode_id', 'city_id'], 'id=' . $area_id, 'area');
                
                $response['error'] = false;
                $response['message'] = 'Address updated successfully';
                $response["id"] = strval($id);
                $response['user_id'] = $user_id;
                $response['name'] = $name;
                $response['type'] = $type;
                $response['mobile'] = $mobile;
                $response['alternate_mobile'] = $alternate_mobile;
                $response['address'] = $address;
                $response['landmark'] = $landmark;
                $response['area_id'] = $area_id;
                $response['area_name'] = $area;
                $response['area'] = $area;
                $response['pincode_id'] = $pincode_id;
                $response['pincode'] = $pincode;
                $response['city'] = $city;
                $response['state'] = $state;
                $response['country'] = $country;
                $response['latitude'] = $latitude == "" ? "0" : $latitude;
                $response['longitude'] = $longitude == "" ? "0" : $longitude;
                $response['is_default'] = $is_default == "" ? "0" : $is_default;
                $response['minimum_free_delivery_order_amount'] = (!empty($d_charges[0]['minimum_free_delivery_order_amount'])) ? $d_charges[0]['minimum_free_delivery_order_amount'] : "0";
                $response['delivery_charges'] = (!empty($d_charges[0]['delivery_charges'])) ? $d_charges[0]['delivery_charges'] : "0";
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong please try again!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such address exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['delete_address'])) && ($_POST['delete_address'] == 1)) {
    /*
    3.delete_address
        accesskey:90336
        delete_address:1
        id:3
    */
    $id  = (isset($_POST['id']) && !empty($_POST['id'])) ? trim($db->escapeString($fn->xss_clean($_POST['id']))) : "";
    if (!empty($id)) {
        if ($fn->is_address_exists($id)) {
            if ($db->delete('user_addresses', 'id=' . $id)) {
                $response['error'] = false;
                $response['message'] = 'Address deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong please try again!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such address exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['get_addresses'])) && ($_POST['get_addresses'] == 1)) {
    /*
    4.get_addresses
        accesskey:90336
        get_addresses:1
        user_id:3
        offset:0    // {optional}
        limit:5     // {optional}
    */
    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? trim($db->escapeString($fn->xss_clean($_POST['user_id']))) : "";
    if (!empty($user_id)) {
        if ($fn->is_address_exists($id = "", $user_id)) {
            $sql = "SELECT count(id) as total from user_addresses where user_id=" . $user_id;
            $db->sql($sql);
            $total = $db->getResult();

            $sql = "select * from user_addresses ua where ua.user_id= " . $user_id . " ORDER BY is_default DESC";
            $db->sql($sql);
            $res = $db->getResult();

            if (!empty($res)) {
                $response['error'] = false;
                $response['message'] = 'Address retrived successfully!';
                $response['total'] = $total[0]['total'];

                for ($i = 0; $i < count($res); $i++) {
                    $res[$i]['area'] = !empty($res[$i]['area']) ? $res[$i]['area'] : "";
                    $res[$i]['name'] = !empty($res[$i]['name']) ? $res[$i]['name'] : "";
                    $res[$i]['mobile'] = !empty($res[$i]['mobile']) ? $res[$i]['mobile'] : "";
                    $res[$i]['pincode'] = !empty($res[$i]['pincode']) ? $res[$i]['pincode'] : "";
                    $res[$i]['pincode_id'] = !empty($res[$i]['pincode_id']) ? $res[$i]['pincode_id'] : "";
                    $res[$i]['city'] = !empty($res[$i]['city']) ? $res[$i]['city'] : "";
                    $res[$i]['city_id'] = !empty($res[$i]['city_id']) ? $res[$i]['city_id'] : "";
                    $res[$i]['latitude'] = (!empty($res[$i]['latitude'])) ? $res[$i]['latitude'] : "0";
                    $res[$i]['longitude'] = (!empty($res[$i]['longitude'])) ? $res[$i]['longitude'] : "0";
                    $res[$i]['minimum_free_delivery_order_amount'] = "0";
                    $res[$i]['delivery_charges'] = "0";
                }
                $response['data'] = array_values($res);
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'User addresse(s) doesn\'t exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}
