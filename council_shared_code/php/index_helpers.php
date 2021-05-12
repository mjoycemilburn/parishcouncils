<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control");
header("Access-Control-Max-Age: 18000");

// set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
require('../includes/council_functions.php');

// As directed by helper_type :
//
// 'build_carousel'                      -   get all the slide files and turn them into a bootstrap carousel
//
// 'build_sections_view_table'           -   generate the website html to view all the entries for all the
//                                           sections
//

    $page_title = 'pcouncil_index_helpers';

date_default_timezone_set('Europe/London');

// connect to the parishcouncils database

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

//////////////////////////////////////////  build_carousel //////////////////////////////////////////

if ($helper_type == "build_carousel") {
    $council_id = $_POST['council_id'];
    $council_name = $_POST['council_name'];

    $return = "
                <div id = 'home' class = 'container-fluid' style = 'margin-top: 2vh; margin-bottom: 4vh; width: 100%; text-align: center; background: Aquamarine;'>
                    <h4 style='padding: 1.5vh;' id = 'websitetitle' >$council_name Parish Council</h4>
                </div>";

    $sql = "SELECT
                slide_title,
                slide_file_extension
            FROM
                slides
            WHERE
                council_id = '$council_id'
            ORDER BY slide_sequence_number ASC;";

    $result = sql_result_for_location($sql, 2);

    // Build the html representing 'carousel_inner'

    $i = 0;

    while ($row = mysqli_fetch_array($result)) {
        $slide_title = $row['slide_title'];
        $slide_file_extension = $row['slide_file_extension'];

        $return .= "
                <div class='carousel-item ";

        if ($i == 0) {
            $return .= "active'>";
        } else {
            $return .= "'>";
        }

        $return .= "
                    <img aria-labelledby='carouselimgcaption$i' src='slides/$slide_title.$slide_file_extension' style='width:100%;'>
                    <div class='carousel-caption'>
                        <p id = 'carouselimgcaption$i'>$slide_title</p>
                    </div>
                </div>";
        $i++;
    }

    echo $return;
}

//////////////////////////////////////////  build_sections_view_table //////////////////////////////////////////

if ($helper_type == "build_sections_view_table") {
    $council_id = $_POST['council_id'];
    $return = '';

    $sql = "SELECT
                section_id,
                section_header,
                section_type,
                section_prefix
            FROM
                sections
            WHERE
                council_id = '$council_id'
            ORDER BY section_sequence_number ASC;";

    $result1 = sql_result_for_location($sql, 3);

    while ($row1 = mysqli_fetch_array($result1)) {
        $section_id = $row1['section_id'];
        $section_type = $row1['section_type'];
        $section_prefix = $row1['section_prefix'];
        $section_header = $row1['section_header'];

        $return .= "<p class='sectionheader'>$section_header</p>";

        $count = 0;
        $entriesa = "<div id = '" . $section_id . "a' style = 'display: block;'>";
        $entriesb = "<div id = '" . $section_id . "b' style = 'display: none;'>";

        if ($section_type == "standard_title") {
            $width = '80%';
        } else {
            $width = '60%';
        }

        $sql = "SELECT
                    entry_date,
                    entry_suffix,
                    entry_title
                FROM
                    entries
                WHERE
                    section_id = '$section_id' AND
                    council_id = '$council_id'
                ORDER BY
                    entry_date DESC;";

        $result2 = sql_result_for_location($sql, 4);

        while ($row2 = mysqli_fetch_array($result2)) {

            // Build the html representing the entries for each section as a pair of divs - the first ('section_id'entriesa') containing
            // just the first four followed by a "more" button, the second (section_id'entriesb') containing all the entreis followed
            // by a "less button"

            if ($section_type == "standard_title") {
                $entry_displaya = $row2['entry_title'];
                $entry_link = $section_id . "_" . $row2['entry_title'] . ".pdf";
            } else {
                $entry_displaya = $section_prefix . " " . $row2['entry_date'];
                $entry_displayb = $row2['entry_suffix'];
                $entry_link = $section_id . "_" . $row2['entry_date'] . "_" . $row2['entry_suffix'] . ".pdf";
            }

            // $entry_standard below is the normal aquamarine form of a section entry, $entry_special
            // is a white-smoke version to signal the presence of the "more" button below. Previously
            // used opacity but this upset accessibility score

            $entry_standard = "
                <p role = 'button' aria-label='Display the pdf file for this entry' tabindex = '0'
                    style ='width: $width;
                            display: inline-block;
                            padding: 1vh;
                            border: 1px solid black;
                            background: Aquamarine;
                            cursor: pointer;'
                    onclick = 'linkto(\"$entry_link\", event);' onkeydown = 'linkto(\"$entry_link\", event);'>
                    <span  style= 'text-decoration: underline;
                                   text-decoration-color: blue;
                                   text-underline-position: under;'>$entry_displaya
                    </span>";

            $entry_special = "
                <p role = 'button' aria-label='Display the pdf file for this entry' tabindex = '0'
                    style ='width: $width;
                            display: inline-block;
                            padding: 1vh;
                            border: 1px solid black;
                            background: WhiteSmoke;
                            cursor: pointer;'
                    onclick = 'linkto(\"$entry_link\", event);' onkeydown = 'linkto(\"$entry_link\", event);'>
                    <span  style= 'text-decoration: underline;
                                   text-decoration-color: blue;
                                   text-underline-position: under;'>$entry_displaya
                    </span>";

            if ($section_type == "date_title") {
                $entry_standard .= "
                    <br>
                    <span>$entry_displayb</span>";
                $entry_special .= "
                    <br>
                    <span>$entry_displayb</span>";
            }

            $entry_standard .= "
                 </p>";

            $entry_special .= "
                 </p>";

            $count++;

            if ($count < 4) {
                $entriesa .= $entry_standard;
            }

            if ($count == 4) {
                if (mysqli_num_rows($result2) > 4) {
                    $entriesa .= $entry_special;
                    $entriesa .= "<p tabindex = '0' role = 'button' aria-label='Display all entries in this section'
                                    onclick = 'togglesubdiv(\"$section_id\", event)' onkeydown = 'togglesubdiv(\"$section_id, event)'style='cursor: pointer;'>
                                    More <img src = '../council_shared_code/img/caret-bottom.svg' alt='caret-bottom symbol'>
                              </p>
                              </div>";
                } else {
                    $entriesa .= $entry_standard;
                }
            }

            $entriesb .= $entry_standard;
        }

        $entriesb .= "<p tabindex = '0'  role = 'button' aria-label='Display just the first four entries in this section'
                        onclick = 'togglesubdiv(\"$section_id\", event)' onkeydown = 'togglesubdiv(\"$section_id\", event)'style='cursor: pointer;'>
                        Less <img src = '../council_shared_code/img/caret-top.svg' alt='caret-top symbol'>
                    </p>
                    </div>";

        $return .= $entriesa;
        $return .= $entriesb;
    }

    
    $return .= "<p style='text-align: center; font-weight: bold; font-style: italic; margin-top:3vh;'>Website design by : " .
                    "<a href = 'https://ngatesystems.com'>Ngatesystems.com</a></p>";


    echo $return;
}
