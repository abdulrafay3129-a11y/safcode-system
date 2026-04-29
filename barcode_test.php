<?php
$code = "TEST123";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barcode Test</title>
    <style>
        body{
            font-family: Arial;
            text-align:center;
            margin-top:50px;
        }
    </style>
</head>
<body>

<h2>Barcode Test</h2>

<div>
    <img src="https://barcode.tec-it.com/barcode.ashx?data=<?php echo $code; ?>&code=Code128&dpi=96" />
</div>

<h3><?php echo $code; ?></h3>

</body>
</html>