<?php
session_start();

if (isset($_SESSION['council_id_for_logged_in_user'])) {
    echo  "Council_id_for_logged_in_user is " . $_SESSION['council_id_for_logged_in_user'] . "<br>";
} else {
    echo "council_id_for_logged_in_user is not set";
}
if (isset($_SESSION['council_name_for_logged_in_user'])) {
    echo  "Council_name_for_logged_in_user is " . $_SESSION['council_name_for_logged_in_user'] . "<br>";
} else {
    echo "council_name_for_logged_in_user is not set";
}
if (isset($_SESSION['council_root_directory'])) {
    echo  "Council_root_directory is " . $_SESSION['council_root_directory'] . "<br>";
} else {
    echo "council_root_directory is not set";
}
