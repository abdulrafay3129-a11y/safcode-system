<?php
require_once "../vendor/autoload.php";

use Picqer\Barcode\BarcodeGeneratorPNG;

$code = $_GET['code'] ?? '';

if(!$code){
    die("No code");
}

$generator = new BarcodeGeneratorPNG();

header("Content-Type: image/png");

echo $generator->getBarcode(
    $code,
    $generator::TYPE_CODE_128,
    2,
    60,
    [0,0,0],       // black bars
    [255,255,255]  // ✅ white background
);