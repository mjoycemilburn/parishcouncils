<?php

function connect_to_database() {
    global $con, $url_root;

    if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1')) {
        $url_root = '../../../';
    } else {
        $current_directory_root = $_SERVER['DOCUMENT_ROOT']; // one level above current directory
        // remove everything after and including "public_html"

        $pieces = explode('public_html', $current_directory_root);
        $url_root = $pieces[0];
    }

    require($url_root . "connect_parishcouncils_db.php");
}

function disconnect_from_database() {
    global $con, $url_root;

    require($url_root . "disconnect_parishcouncils_db.php");
}

function sql_result_for_location($sql, $location) {
    global $con, $page_title;

    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Oops - database access %failed%. in $page_title location $location. Error details follow : " . mysqli_error($con);

        $sql = "ROLLBACK";
        $result = mysqli_query($con, $sql);

        disconnect_from_database();
        exit(1);
    }

    return $result;
}

function unique_entry_key($council_id, $section_id, $section_type, $entry_date, $entry_title) {

    // return true if the key combination $section_id/$entry_date or $section_id/$entry_title is absent
    // from the entries table for the supplied $section_type

    if ($section_type === "date_title") {
        $sql = "SELECT
                    section_id
                FROM entries
                WHERE
                    council_id = '$council_id' AND
                    section_id = '$section_id' AND
                    entry_date = '$entry_date';";
    } else {
        $sql = "SELECT
                     section_id
                 FROM entries
                 WHERE
                     council_id = '$council_id' AND
                     section_id = '$section_id' AND
                     entry_title = '$entry_title';";
    }

    $result = sql_result_for_location($sql, 2);

    $row = mysqli_fetch_assoc($result);
    if (mysqli_num_rows($result) >= 1) {
        return false;
    } else {
        return true;
    }
}

function pr($var) {
    print '<pre>';
    print_r($var);
    print '</pre>';
}
