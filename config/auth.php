<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(){
    return isset($_SESSION['user_id'], $_SESSION['session_token']);
}

function requireLogin(){
    if(!isLoggedIn()){
        header("Location: ../auth/login.php");
        exit;
    }
}

function checkRole($roles = []){

    requireLogin();

    if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)){

        header("Location: ../auth/login.php?message=Unauthorized");
        exit;
    }
}