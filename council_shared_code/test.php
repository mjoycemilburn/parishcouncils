<?php
session_start();

if (isset($_SESSION['council_id_for_logged_in_user'])) {
    echo $_SESSION['council_id_for_logged_in_user'];
} else {
    echo "council_id_for_logged_in_user is not set";
}
if (isset($_SESSION['council_name_for_logged_in_user'])) {
    echo $_SESSION['council_name_for_logged_in_user'];
} else {
    echo "'council_name_for_logged_in_user is not set";
}
if (isset($_SESSION['council_root_directory'])) {
    echo isset($_SESSION['council_root_directory']);
} else {
    echo "council_root_directory is not set";
}
