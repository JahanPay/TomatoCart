<?php
$form = $_POST["form"];

 echo ('<div style="display:none;">'.$form.'</div><script>document.forms["jahanpay"].submit();</script>');

?>