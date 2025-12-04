<?php require_once(dirname(__FILE__) . '/tampil/index.php');?> 
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<title>SIPADU ISI SURAKARTA</title>
<link rel="shortcut icon" href="template/images/<?php echo PT_ICON;?>" type="image/x-icon" /> 
<?php echo $setting_tampilan;?>
</head>
<body>
<?php

if (!isset($_SESSION[ACAK_SESS])) {  
 	//require_once(dirname(__FILE__) . '/template/login.php');
	header("Location: https://sipadu.isi-ska.ac.id/eis/index.php");
}else{
	require_once(dirname(__FILE__) . '/template/index.php');
	//echo "Perbaikan";
}
 ?>
<script>
	$(document).ready(function() {
         $('.demo').ntm();
		 $('#menu').slicknav();
	});
	$("#loading-img").hide();
</script>
</body> 
</html>
