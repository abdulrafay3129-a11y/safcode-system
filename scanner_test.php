<!DOCTYPE html>
<html>
<head>
<title>Barcode Scanner Test</title>

<style>
body{
    font-family: Arial;
    text-align:center;
    margin-top:30px;
    background:#f5f5f5;
}

.barcode-box{
    margin-bottom:30px;
}

input{
    padding:15px;
    width:300px;
    font-size:20px;
    text-align:center;
    border:2px solid #333;
    border-radius:8px;
}

#result{
    margin-top:20px;
    font-size:24px;
    color:green;
    font-weight:bold;
}

h2{
    margin-bottom:10px;
}
</style>
</head>

<body>

<h2>🔍 Barcode Scanner System</h2>

<!-- BARCODE UPPER SIDE -->
<div class="barcode-box">
    <img src="https://barcode.tec-it.com/barcode.ashx?data=TEST123&code=Code128&dpi=96" />
    <p><b>TEST123</b></p>
</div>

<!-- SCANNER INPUT -->
<input type="text" id="scannerInput" placeholder="Scan here..." autofocus>

<!-- RESULT -->
<div id="result">Waiting for scan...</div>

<script>
let input = document.getElementById("scannerInput");
let result = document.getElementById("result");

// force focus (scanner ke liye zaroori)
function keepFocus(){
    input.focus();
}
setInterval(keepFocus, 300);
window.onload = keepFocus;
document.addEventListener("click", keepFocus);

// scanner handling (no ENTER dependency)
input.addEventListener("input", function(){

    clearTimeout(this.timer);

    this.timer = setTimeout(function(){

        let code = input.value.trim();

        if(code.length > 0){
            result.innerHTML = "✅ Scanned: " + code;

            // clear after show
            setTimeout(() => {
                input.value = "";
            }, 300);
        }

    }, 200);

});
</script>

</body>
</html>