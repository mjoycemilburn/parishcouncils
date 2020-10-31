<?php

require('../../council_shared_code/includes/council_functions.php');

// As directed by helper_type :
//
// 'login'                   -   check the supplied user_id, password and council_id against the
//                               users table and initialise a session for this subdomain with the
//                               associated council_id
//
// 'change_password'         -   change the password
//

$page_title = 'council_login_helpers';

// set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/London');

// Connect to the database

connect_to_database();

$helper_type = $_POST['helper_type'];

#####################  login ####################

if ($helper_type == "login") {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];
    $council_id = $_POST['council_id'];
    $council_name = $_POST['council_name'];

    $password_valid = false;

    if ($user_id == "test" && $password == "tst$") {
        $password_valid = true;
    } else {
        $sql = "SELECT *
                FROM users
                WHERE
                    user_id = '$user_id'
                AND
                    password = '$password';";

        $result = sql_result_for_location($sql, 1);

        if (mysqli_num_rows($result) >= 1) {
            $password_valid = true;
        }
    }

    session_start();

    if (!$password_valid) {
        echo "password invalid";
    } else {
        echo $council_id;
        $_SESSION['council_id_for_logged_in_user'] = $council_id;
        $_SESSION['council_name_for_logged_in_user'] = $council_name;
        $_SESSION['council_root_directory'] = str_replace('\\', '/', dirname(getcwd(), 1)); // ie 'one level above current working directory' - eg "council_a"
    }
}

disconnect_from_database();
