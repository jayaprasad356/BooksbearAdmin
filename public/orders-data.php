<?php
include_once('includes/variables.php');
include_once('includes/crud.php');
include_once('includes/custom-functions.php');
$function = new custom_functions();
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $ID = $db->escapeString($function->xss_clean($_GET['id']));
} else { ?>
    <script>
        alert("Something went wrong, No data available.");
        window.location.href = "orders.php";
    </script>
<?php
}
$currency = $function->get_settings('currency');

// create array variable to handle error
$update_order_permission = $permissions['orders']['update'];
$allowed = ALLOW_MODIFICATION;
$seller_name = "";

$error = array();

$sql = "SELECT oi.*,oi.tax_amount as amount_tax,oi.tax_percentage as amount_percentage,o.final_total as payable_total,oi.id as order_item_id,v.product_id,v.measurement_unit_id,p.cancelable_status,o.*,o.total as order_total,o.wallet_balance,oi.active_status as oi_active_status,u.email,u.name as uname,u.country_code FROM `order_items` oi JOIN users u ON u.id=oi.user_id LEFT JOIN product_variant v ON oi.product_variant_id=v.id LEFT JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id WHERE o.id=$ID";
$db->sql($sql);
$res = $db->getResult();
$user_address = $res[0]['address'];
$items = [];
foreach ($res as $row) {
    $data = array($row['product_id'], $row['product_variant_id'], $row['product_name'], $row['variant_name'], 
    $row['measurement_unit_id'], $row['quantity'], $row['discounted_price'], $row['price'], $row['oi_active_status'], 
    $row['cancelable_status'], $row['order_item_id'], $row['sub_total'], $row['tax_amount'], $row['tax_percentage'], 
    $row['seller_id'], $row['delivery_boy_id'],$row['user_id']);
    array_push($items, $data);
}
$count_standard_product = 1;
?>
    <style>
        @media (min-width: 992px) {
            .col-md-3 {
                width: 20% !important;

            }
        }

        .track {
            position: relative;
            background-color: #ddd;
            height: 7px;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            margin-bottom: 60px;
            margin-top: 50px
        }

        .track .step {
            -webkit-box-flex: 1;
            -ms-flex-positive: 1;
            flex-grow: 1;
            width: 25%;
            margin-top: -18px;
            text-align: center;
            position: relative
        }

        .track .step.active:before {
            background: #45b4ff;
        }

        .track .step::before {
            height: 7px;
            position: absolute;
            content: "";
            width: 100%;
            left: 0;
            top: 18px
        }

        .track .step.active .icon {
            background: #45b4ff;
            color: #fff
        }

        .track .icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            position: relative;
            border-radius: 100%;
            background: #ddd
        }

        .track i {
            width: 15px;
            padding-top: 11.5px;
        }

        .track .step.active .text {
            font-weight: 400;
            color: #000
        }

        .track .text {
            display: block;
            margin-top: 7px
        }
    </style>
<section class="content-header">
    <h1>Order Detail</h1>
    <?php echo isset($error['update_data']) ? $error['update_data'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <?php
            if ($permissions['orders']['read'] == 1) {

                if ($permissions['orders']['update'] == 0) { ?>
                    <div class="alert alert-danger topmargin-sm">You have no permission to update orders.</div>
                <?php } ?>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Order Detail</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <table class="table table-bordered">
                            <tr>
                                <input type="hidden" name="hidden" id="order_id" value="<?php echo $res[0]['id']; ?>">
                                <th style="width: 10px">ID</th>
                                <td><?php echo $res[0]['id']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Name</th>
                                <td><?php echo $res[0]['uname']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Email</th>
                                <td><?php echo $res[0]['email']; ?></td>
                            </tr>

                            <tr>
                                <th style="width: 10px">Contact</th>
                                <td><?php echo $res[0]['mobile']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">O. Note</th>
                                <td><?php echo $res[0]['order_note']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Area</th>
                                <?php
                                if (!empty($res[0]['area_id'])) {
                                    $area_id = $res[0]['area_id'];
                                    $sql = "SELECT * FROM `area` WHERE id =$area_id";
                                    $db->sql($sql);
                                    $res_areas = $db->getResult();
                                } else {
                                    $res_areas = array();
                                }
                                ?>
                                <td><?= (!empty($res_areas)) ? $res_areas[0]['name'] : "" ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Pincode</th>
                                <?php
                                $pincode_id = $res[0]['pincode_id'];
                                $sql = "SELECT * FROM `pincodes` WHERE id =$pincode_id";
                                $db->sql($sql);
                                $res_pincodes = $db->getResult();
                                ?>
                                <td><?= (!empty($res_pincodes)) ? $res_pincodes[0]['pincode'] : "" ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">OTP</th>
                                <td><?= (isset($res[0]['otp']) && !empty($res[0]['otp'])) ? $res[0]['otp'] : "-" ?></td>
                            </tr>
                            <?php
                            // $sql = "SELECT id,name FROM delivery_boys WHERE status=1";
                            $sql = "SELECT id,name,pincode_id FROM delivery_boys WHERE status=1 and FIND_IN_SET($pincode_id, pincode_id) ";
                            $db->sql($sql);
                            $result = $db->getResult();
                            ?>
                            <tr>
                                <th style="width: 10px">Items</th>

                                <td>
                                    <div class="container-fluid">
                                    <div class="col-md-12">
                                                    <h4>Standard Shipping Order items</h4><small><a data-toggle="modal" data-target="#howtomanage"> How to manage shiprocket order ?</a></small>
                                                    <div class="modal  fade" id="howtomanage" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="exampleModalLabel">How to manage shiprocket order </h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="container">
                                                                        <div class="row">
                                                                            <div class="col-md-12">
                                                                                <h5><b>Create shiprocket order</b></h5>
                                                                                <b>Steps:</b><br>
                                                                                1.Select order items which you have to add in parcel and click on create order button<br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/create-ordeer.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                2.After create order generate AWB code(its unique number use for identify order) like this<br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/awb.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                3.Send request for pickup <br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/send-pickup-request.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                4.Track order <br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/trackin.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                5.Cancel order <br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/cancel order.png" alt="" style="max-width:75%;">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Standard Shipping Pending Creating orders Order items </h5>
                                <div id="result-shiprocket"></div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" disabled class="btn btn-primary create-shiprocket" data-toggle="modal" data-target="#exampleModal">
                                    Create Shipprocket Order
                                </button>
                            </div>
                        </div>
                                        <?php $total = 0;
                                        foreach ($items as $item) {
                                        ?>
                                            <div class="card col-md-3">
                                                <div class="card-body">
                                                    <?php if ($item[8] == 'received') {
                                                        $active_status = '<label class="label label-primary">' . $item[8] . '</label>';
                                                    }
                                                    if ($item[8] == 'processed') {
                                                        $active_status = '<label class="label label-info">' . $item[8] . '</label>';
                                                    }
                                                    if ($item[8] == 'shipped') {
                                                        $active_status = '<label class="label label-warning">' . $item[8] . '</label>';
                                                    }
                                                    if ($item[8] == 'delivered') {
                                                        $active_status = '<label class="label label-success">' . $item[8] . '</label>';
                                                    }
                                                    if ($item[8] == 'returned' || $item[8] == 'cancelled') {
                                                        $active_status = '<label class="label label-danger">' . $item[8] . '</label>';
                                                    }
                                                    if ($item[8] == 'awaiting_payment') {
                                                        $active_status = '<label class="label label-secondary">Awaiting Payment</label>';
                                                    }
                                                    $array[] = $item[8];
                                                    if (!empty($item[14])) {
                                                        $s_id = $item[14];
                                                        $db->sql("SET NAMES 'utf8'");
                                                        $sql = "SELECT `name` FROM `seller` where id= $s_id ORDER BY id DESC";
                                                        $db->sql($sql);
                                                        $seller_name = $db->getResult();
                                                        $seller_name = (!empty($seller_name)) ? $seller_name[0]['name'] : "<span class='label label-danger'>Not Assigned</span>";
                                                    }
                                                    $total += $subtotal = ($item[6] != 0 && $item[6] < $item[7]) ? ($item[6] * $item[5]) : ($item[7] * $item[5]);
                                                    echo "<br><input type='checkbox' data-qty='$item[5]' data-order-item-id=" . $item[10] . "  data-sub-total=" . $item[11] . " name='order_items[]' class='seller_id' data-seller-id=" . $s_id . " value=" . $item[10] . ">" . "</br>";
                                                            
                                                    echo  "</br>" . "<b>Product Id : </b>" . $item[0] . "  " . $active_status . "</br>";
                                                    if (!empty($seller_name)) {
                                                        echo " <b>Seller Name : </b>" . $seller_name . "</br>";
                                                    }
                                                    echo "<b> Product Variant Id : </b>" . $item[1] . "</br>";
                                                    echo " <b>Name : </b>" . $item[2] . "(" . $item[3] . ")</br>";
                                                    echo " <b>Quantity : </b>" . $item[5] . "</br>";
                                                    echo " <b>Price(" . $currency . ") : </b>" . $item[7] . "</br>";
                                                    echo " <b>Discounted Price(" . $currency . ") : </b>" . $item[6] . "</br>";
                                                    echo " <b>Tax Amount(" . $currency . ") : </b>" . $item[12] . "</br>";
                                                    echo " <b>Tax Percentage(%) : </b>" . $item[13] . "</br>";
                                                    echo " <b>Subtotal(" . $currency . ") : </b>" . $item[11] . "  ";

                                                    $is_product = $function->get_data($column = ['id'], 'id=' . $item[1], 'product_variant');
                                                    if (!empty($is_product)) {
                                                        echo "<a href='" . DOMAIN_URL . "/view-product-variants.php?id=" . $item[0] . "' class='btn btn-success btn-xs' title='View Product'><i class='fa fa-eye'></i> Product</a> <br> <br>";
                                                    }
                                                    ?>

                                                    <select name="status" class="form-control status">
                                                        <option value="awaiting_payment" <?= ($item[8] == "awaiting_payment") ? 'selected' : ''; ?> data-value='<?= $item[0] ?>' data-value1='<?= $item[10] ?>'>Awaiting Payment</option>
                                                        <option value="received" <?= ($item[8] == "received") ? 'selected' : ''; ?> data-value='<?= $item[0] ?>' data-value1='<?= $item[10] ?>'>Received</option>
                                                        <option value="processed" <?= ($item[8] == "processed") ? 'selected' : ''; ?> data-value='<?= $item[0] ?>' data-value1='<?= $item[10] ?>'>Processed</option>
                                                        <option value="shipped" <?= ($item[8] == "shipped") ? 'selected' : ''; ?> data-value='<?= $item[0] ?>' data-value1='<?= $item[10] ?>'>Shipped</option>
                                                        <option value="delivered" <?= ($item[8] == "delivered") ? 'selected' : ''; ?> data-value='<?= $item[0] ?>' data-value1='<?= $item[10] ?>'>Delivered</option>
                                                        <option value="cancelled" <?= ($item[8] == "cancelled") ? 'selected' : ''; ?> data-value='<?= $item[0] ?>' data-value1='<?= $item[10] ?>'>Cancel</option>
                                                        <option value="returned" <?= ($item[8] == "returned") ? 'selected' : ''; ?> data-value='<?= $item[0] ?>' data-value1='<?= $item[10] ?>'>Returned</option>
                                                    </select>
                                                    </br>

                                                    <select name='deliver_by' class='form-control deliver_by' required>
                                                        <option value=''>Select Delivery Boy</option>
                                                        <?php foreach ($result as $row1) {
                                                            if ($item[15] == $row1['id']) { ?>
                                                                <option value='<?= $row1['id'] ?>' selected data-value1='<?= $item[10]  ?>'><?= $row1['name'] ?></option>
                                                            <?php } else { ?>
                                                                <option value='<?= $row1['id'] ?>' data-value1='<?= $item[10]  ?>'><?= $row1['name'] ?></option>
                                                        <?php }
                                                        } ?>
                                                    </select>
                                                    <hr>

                                                    <div class="clearfix">
                                                        <a href="#" title='update' id="submit_btn" class="btn btn-primary col-sm-12 col-md-12 update_order_item_status " data-value1='<?= $item[10] ?>' data-value2='<?= $item[8] ?>'>Update</a>
                                                        <hr> <?php $whatsapp_message = "Hello " . ucwords($res[0]['uname']) . ", Your order with ID : " . $res[0]['id'] . " is " . ucwords($item[8]) . ". Please take a note of it. If you have further queries feel free to contact us. Thank you."; ?>
                                                        <a class=" col-sm-12 btn btn-success" href="https://api.whatsapp.com/send?phone=<?= '+' . $res[0]['country_code'] . ' ' . $res[0]['mobile']; ?>&text=<?= $whatsapp_message; ?>" target='_blank' title="Send Whatsapp Notification"><i class="fa fa-whatsapp"></i> Send Whatsapp Notification</a>

                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Total (<?= $settings['currency'] ?>)</th>
                                <td><?php echo $res[0]['order_total']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">D.Charge (<?= $settings['currency'] ?>)</th>
                                <td><?php echo $res[0]['delivery_charge']; ?></td>

                            </tr>

                            <?php if ($res[0]['discount'] > 0) {
                                $discounted_amount = $res[0]['total'] * $res[0]['discount'] / 100; /*  */
                                $final_total = $res[0]['total'] - $discounted_amount;
                                $discount_in_rupees = $res[0]['total'] - $final_total;
                                $discount_in_rupees = $discount_in_rupees;
                            } else {
                                $discount_in_rupees = 0;
                            } ?>
                            <tr>
                                <th style="width: 10px">Disc. <?= $settings['currency'] ?>(%)</th>
                                <td><?php echo  $discount_in_rupees . '(' . round($res[0]['discount'], 2) . '%)'; ?></td>
                            </tr>

                            <tr>
                                <th style="width: 10px">Promo Disc. (<?= $settings['currency'] ?>)</th>
                                <td><?php echo $res[0]['promo_discount']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Wallet Used</th>
                                <td><?php echo $res[0]['wallet_balance']; ?></td>
                            </tr>
                            <input type="hidden" name="total_amount" id="total_amount" value="<?php echo $res[0]['payable_total']; ?>">
                            <tr>
                                <th style="width: 10px">Discount %</th>
                                <td><input type="number" class="form-control" id="input_discount" name="input_discount" value="<?php echo $res[0]['discount']; ?>" min=0 max=100></td>
                                <td><a href="#" title='save_discount' class="btn btn-primary form-control update_order_total_payable" data-id='<?= $row['id']; ?>'>Save</a></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Payable Total(<?= $settings['currency'] ?>)</th>
                                <td><input type="number" class="form-control" id="final_total" name="final_total" value="<?= $res[0]['payable_total']; ?>" disabled></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Payment Method</th>
                                <td><?php echo $res[0]['payment_method']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Promo Code</th>
                                <td><?= (!empty($res[0]['promo_code']) || $res[0]['promo_code'] != null) ? $res[0]['promo_code'] : ""; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Address</th>
                                <td><?php echo $res[0]['address']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Order Date</th>
                                <td><?php echo date('d-m-Y', strtotime($row['date_added'])); ?></td>
                            </tr>
                            <tr>
                                <th style="width: 10px">Delivery Time</th>
                                <td><?php echo $res[0]['delivery_time']; ?></td>
                            </tr>
                        </table>
                        <div class="box-footer clearfix">
                            <?php
                            $check_array = array("awaiting_payment", "cancelled", "returned");
                            $result1 = array_diff($array, $check_array);
                            if (!empty($result1)) { ?>
                                <button class="btn btn-primary pull-right" onclick="myfunction()"><i class="fa fa-download"></i>Generate Invoice</button>
                            <?php } else { ?>
                                <button class="btn btn-primary disabled pull-right"><i class="fa fa-download"></i> Generate Invoice</button>
                            <?php } ?>
                        </div>
                    </div>



                </div>
            <?php } else { ?>
                <div class="alert alert-danger">You have no permission to view orders</div>
            <?php }  ?>
            <!-- /.box -->
        </div>

    </div>
</section>
<div class="modal fade " id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="" method="post" id="create_order_form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Create Shipprocket Order Parcel</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-4 mt-5">
                                    <label for="" class="">seller Pickup location: </label>
                                </div>
                                <div class="col-md-8">

                                    <input type="text" name="seller_pickup_location" class="form-control" value="" id="pickup-location">

                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mt-5">
                                    <label for="" class="">seller Pickup pincode: </label>
                                </div>
                                <div class="col-md-8">

                                    <input type="number" name="seller_pickup_pincode" class="form-control" value="" id="pickup-pincode">

                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="order_id" value="<?= $_GET['id'] ?>">
                        <div id="create_order_result">

                        </div>
                        <input type="hidden" name="order_item_ids[]" id="order_item_ids" value="'">
                        <label for="">Total Weight of Boox</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">Weight </label><small> Kg</small>
                                <input type="number" name="weight" required class="form-control weight" placeholder="enter weight of parcel" id="">
                            </div>
                            <div class="col-md-3">
                                <label for="">Height </label><small> cms</small>
                                <input type="number" name="hieght" required class="form-control" placeholder="enter weight of parcel" id="">
                            </div>
                            <div class="col-md-3">
                                <label for="">Breadth </label><small> cms</small>
                                <input type="number" name="breadth" required class="form-control" placeholder="enter weight of parcel" id="">
                            </div>

                            <div class="col-md-3">
                                <label for="">Length</label><small> cms</small>
                                <input type="number" name="length" required class="form-control" placeholder="enter weight of parcel" id="">
                            </div>
                        </div>
                        <label for="" class="parcel_error text-danger"></label>

                    </div>
                    <input name="subtotal" type="hidden" id="subtotal">
                    <input type="hidden" name="create_order_btn" value="1">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="close" data-dismiss="modal">Close</button>
                        <button type="submit" id="create_order_btn" class="btn btn-success create_order_btn">Create orders</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <style>

    </style>

    <div class="modal fade bd-example-modal-lg" id="track_order" tabindex="-1" role="dialog" aria-labelledby="track_order" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Trak Order</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Shipment ID: <label id="show_shipment_id"></label></h6>
                        </div>
                        <div class="col-md-6">
                            <h6 class="current_status"></h6>
                        </div>
                    </div>


                    <div class="track">
                        <div class="step  active"> <span class="icon"> <i class="fa fa-check"></i> </span> <span class="text">Request Pickup Sended</span> </div>
                        <div class="step  pickuped"> <span class="icon"> <i class="fa fa-user"></i> </span> <span class="text">Pickuped</span> </div>
                        <div class="step  on-the-way"> <span class="icon"> <i class="fa fa-truck"></i> </span> <span class="text"> On the way </span> </div>
                        <div class="step delivered"> <span class="icon"><i class="fa fa-check-square" aria-hidden="true" style="color:white;"></i></span> <span class="text">Delivered successfuly</span> </div>
                    </div>
                    <hr>


                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-md-4 pt-1 shiprocket-link"></div>
                        <div class="col-md-8"> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
    var allowed = '<?= $allowed; ?>';
    var delivery_by = "";
    $(".deliver_by").change(function(e) {
        delivery_by = $(this).val();
    });
    var status = "";
    $(".status").change(function(e) {
        status = $(this).val();
    });
    $(document).on('click', '.update_order_item_status', function(e) {
        e.preventDefault();
        if (allowed == 0) {
            alert('Sorry! This operation is not allowed in demo panel!.');
            window.location.reload();
            return false;
        }
        var update_permission = '<?= $update_order_permission; ?>';
        if (update_permission == 0) {
            alert('Sorry! you have no permission to update orders.');
            window.location.reload();
            return false;
        }
        var status1 = status;
        var id = $('#order_id').val();
        var item_id = $(this).data('value1');
        var delivery_by1 = delivery_by;
        // alert("STATUS : " + status1 + " DELIVER: " + delivery_by + " ITEM ID: " + item_id);
        var dataString = 'update_order_status=1&order_id=' + id + '&status=' + status1 + '&order_item_id=' + item_id + '&delivery_boy_id=' + delivery_by + '&ajaxCall=1';
        if (confirm("Are you sure? you want to change the order item status")) {
            $.ajax({
                url: "api-firebase/order-process.php",
                type: "POST",
                data: dataString,
                dataType: "json",
                success: function(data) {
                    if (data.error == true) {
                        alert(data.message);
                        location.reload(true);
                    } else {
                        alert(data.message);
                        location.reload(true);
                    }
                    $('#status option:selected').attr('disabled', false);
                }
            });
        }
    });
    

    $(document).on('click', '.update_order_total_payable', function(e) {
        e.preventDefault();
        if (allowed == 0) {
            alert('Sorry! This operation is not allowed in demo panel!.');
            window.location.reload();
            return false;
        }
        var update_permission = '<?= $update_order_permission; ?>';
        if (update_permission == 0) {
            alert('Sorry! you have no permission to update orders.');
            window.location.reload();
            return false;
        }
        var discount = $('#input_discount').val();
        var total_payble = $('#final_total').val();
        var id = $('#order_id').val();
        var dataString = 'update_order_total_payable=true&id=' + id + '&discount=' + discount + '&total_payble=' + total_payble + '&ajaxCall=1';
        $.ajax({
            url: "api-firebase/order-process.php",
            type: "POST",
            data: dataString,
            beforeSend: function() {
                $(this).html('...');
            },
            dataType: "json",
            success: function(data) {
                var result = $.map(data, function(value, index) {
                    return [value];
                });
                alert(result[1]);
                if (!result[0]) {}
                location.reload();
            }

        });
    });

    function myfunction() {
        var create = '<?php echo $permissions['reports']['create']; ?>';
        if (create == 0) {
            alert('You have no permission to create invoice');
            return false;

        }
        window.location.href = 'invoice.php?id=<?php echo $res[0]['id']; ?>';
    }
    $('#input_discount').on('input', function() {
        var total = $("#total_amount").val();
        var discount = $('#input_discount').val();
        discounted_amount = total * discount / 100;
        final_total = total - discounted_amount;
        if (discount >= 0) {
            $("#final_total").val(Math.round((final_total + Number.EPSILON) * 100) / 100);
        }
    });

    $('.seller_id').on('click', function() {


        var seller_id = $(this).data('seller-id');
    

        var all = $('.seller_id');
        if ($(this).is(':checked')) {
            $('.create-shiprocket').attr('disabled', false);
            for (var i = 0; i < all.length; i++) {
                if ($(all[i]).data('seller-id') == seller_id ) {
                    if ($(all[i]).is(':checked')) {
                        $(all[i]).addClass('checked')
                    }
                    $(all[i]).attr("disabled", false)
                } else {
                    $(all[i]).attr("disabled", true)
                }
            }
        } else {
            for (var i = 0; i < all.length; i++) {
                if ($(all[i]).is(':checked')) {
                    $(all[i]).removeClass("checked")

                    $(all[i]).attr("disabled", false)
                } else {

                    $(all[i]).removeClass("checked")

                    $(all[i]).attr("disabled", false)
                }

            }
}
});

var weight = 0;
    $('.create-shiprocket').on('click', function() {
        weight = 0;
        var all = $('.checked');
        var temparr = [];
        var sub_total = 0;
        for (var i = 0; i < all.length; i++) {
            if ($(all[i]).is(':checked')) {
                var seller_id = $(all[i]).data('seller-id');
                $('#create_order_result').html('<input type="hidden" name="select_seller_id" id="create_order_seller_id" value="' + seller_id + '">')
                // temparr[$(all[i]).data('product-id')] = $(all[i]).data('product-name');
                if (seller_id != "") {
                    temparr = [...temparr, $(all[i]).data('order-item-id')]
                    sub_total += parseFloat($(all[i]).data('sub-total'))
                    weight = 0 ;
                }
            }
        }

        $('#pickup-location').val('')
        $('#order_item_ids').attr('value', temparr);
        $('#subtotal').attr('value', sub_total);
        $('.weight').attr('value', weight);
    });
</script>
<script>
        $('#create_order_form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(document.getElementById("create_order_form"));
        $.ajax({
            type: 'POST',
            url: "public/db-operation.php",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            beforeSend: function() {
                
                $('.create_order_btn').html('Please Wait....').attr('disabled', true);
            },
            error: function(request, error) {
                alert(request.responseText);
                console.log(request)
            },
            success: function(data) {
                if (data.error == false) {
                    $('#close').trigger('click');
                    $('.create_order_btn').html('Create Order').attr('disabled', false);
                    $('#result-shiprocket').html('<h4><label class="label m-5 label-success">' + data.message + '</label></h4>')
                    //location.reload();
                } else {
                    $('#close').trigger('click');
                    $('.create_order_btn').html('Create Order').attr('disabled', false);
                    $('#result-shiprocket').html('<h4><label class="label m-5 label-danger">' + data.message + '</label></h4>')
                    data.data.forEach(Element => {
                        $('#result-shiprocket').append('<label class="label m-5 label-danger">' + Element + '</label>')

                    });
                }
            }
        });

    });
</script>

<?php $db->disconnect(); ?>