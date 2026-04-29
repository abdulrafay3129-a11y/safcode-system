<?php
$code = $_GET['code'] ?? 'TEST123';
?>

<!DOCTYPE html>
<html>
<head>
<title>Barcode Generator</title>

<style>
body{
    text-align:center;
    background:#fff;
    font-family:Arial;
}

input{
    padding:15px;
    width:300px;
    font-size:20px;
    text-align:center;
    border:2px solid #000;
    margin-top:20px;
}
</style>
</head>

<body>

<h2>Barcode Generator + Scanner</h2>

<img src="barcode_image.php?code=<?= urlencode($code) ?>" />

<p><b><?= $code ?></b></p>

<input type="text" id="scanBox" placeholder="Scan here..." autofocus>

<script>
let input = document.getElementById("scanBox");

input.addEventListener("input", function(){

    clearTimeout(this.timer);

    this.timer = setTimeout(()=>{

        let val = input.value.trim();

        if(val.length > 0){

            fetch("../helpers/barcode.php?code=" + val)
            .then(res => res.json())
            .then(data => {

                if(data.success){
                    alert("✅ Attendance Marked: " + data.name);
                }else{
                    alert("❌ " + data.msg);
                }

            });

            input.value="";
        }

    },200);
});
</script>

</body>
</html>