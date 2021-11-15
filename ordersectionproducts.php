<?php
// start session
session_start();

include_once('includes/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");
$id = $_GET['id'];
if(isset($_POST['submit'])){
    $pid= implode(',',$_POST['product_ids']);
    // $city = (isset($_POST['product_ids']) ? $_POST['product_ids']: null);
    // foreach($city as $i)
    $sql = "UPDATE sections SET product_ids = '$pid' WHERE id = $id";
    $db->sql($sql);
    header("Location: sections.php"); 
    exit();

    

}



?>
<?php include "header.php"; ?>

<html>

<head>
    
    <title>Document</title>
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js">
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</head>
<body>
<div class="content-wrapper">
<h1 class="">Order your Section</h1>
<form id="section_form" method="post"   enctype="multipart/form-data">
<div>
 <div class="form-group"  style="padding-top:150px">
  
  <div class="col-md-6">
  
   <select name='product_ids[]' id='product_ids' class='form-control' placeholder='Enter the product IDs you want to display specially on home screen of the APP in CSV formate' multiple="multiple" required>
                                        <?php 
                                        $id = $_GET['id'];
                                        $sql = 'select * from `sections` where `id` = '.$id;
                                        $db->sql($sql);

                                        $result = $db->getResult();
                                        $pids = explode(",", $result[0]['product_ids']);
                                        foreach($pids as $pid) {
                                            $sql = 'select id,name from `products` where `id` = '.$pid;
                                            $db->sql($sql);

                                        $result = $db->getResult();
                                        
                                            ?>
                                            
                                            
                                            <option value='<?= $result[0]['id'] ?>'><?= $result[0]['name'] ?></option>
                                        <?php } ?>
                                        
                                            

                                    </select>
                                    <a href="javascript:;" onclick="selectAll();">Select All</a> | 
  </div>
 </div>
 <div class="row">
 <div class="col-md-6">

 <button type="button" class="btn btn-default reorder" id="Up">
    <span class="glyphicon glyphicon-chevron-up"></span>
  </button>
 </div>
 <div class="col-md-6">
 <button type="button" class="btn btn-default reorder" id="Down">
    <span class="glyphicon glyphicon-chevron-down"></span>
  </button>
 </div>
 </div>


</div>

<input type="submit" name="submit"  onclick="submitid();" class="btn-primary btn update" value="Update" id='submit_btn' />

                        
</form>
</div>
<script>
$(document).ready(function(){
    $('.reorder').click(function(){
        var $options = $('#product_ids option:selected'),
            $this = $(this);
        if($options.length){
            ($this.attr("id") == 'Up') ? $options.first().prev().before($options) 
            : $options.last().next().after($options);
        }
    });
});
</script>
<script>
var select_ids = [];
$(document).ready(function(e) {
	$('select#product_ids option').each(function(index, element) {
		select_ids.push($(this).val());
	})
});

function selectAll()
{
	$('select#product_ids').val(select_ids);
}
function submitid()
{
	alert("Do you confirm ?");
}

</script>

    
</body>
</html>
<?php include "footer.php"; ?>