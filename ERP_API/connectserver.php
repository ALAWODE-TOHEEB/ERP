
<?php
$serverName = "192.168.0.2,1433";
$serverNametran = "192.168.0.2,1433";
 $connectionInfor = array("Database"=>"MIS_New2","uid"=>"sa","pwd"=>"password10$");
$connr= sqlsrv_connect( $serverName,$connectionInfor);
date_default_timezone_set('Africa/Lagos');
if($connr) {
    //  echo "Connection established.<br />";
    $connb = $conn = $connr;
}else{
     echo "Connection could not be established.<br />";
die( print_r( sqlsrv_errors(), true)); 
}
?>
