<?php
include 'PHPQrcode/PHPQrcode.php';  


$QRcode = new QRcode();
$result = $QRcode->png('123');


echo '<img src="'.$result.'">';



?>