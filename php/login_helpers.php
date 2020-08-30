<?php
 
if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1')) {
    require '../../pc_credentials.php';
    } else {
require $_SERVER['DOCUMENT_ROOT'] . '/../pc_credentials.php';
    }

// note - $_SERVER['DOCUMENT_ROOT'] returns the folder immediately below the one at which you're operating

session_start();

// function-specific code to service login.html routine

$supplied_password = $_POST['password'];

if ($supplied_password == $stored_password_from_pc_credentials) {
    $_SESSION['pc_user_logged_in'] = 'true';
    echo "accepted";
} else {
    unset($_SESSION['pc_user_logged_in']);
    echo "rejected";
}   