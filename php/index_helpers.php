<?php

require('../includes/pc_functions.php');

# As directed by helper_type :    
#   
# 'build_carousel'                      -   get all the slide files and turn them into a bootstrap carousel                                          
#                                                                                                                           
# 'build_sections_view_table'           -   generate the website html to view all the entries for all the 
#                                           sections                                                                                                                       
#

$page_title = 'pcouncil_entries_database_helpers';

# set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/London');

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  build_carousel ####################

if ($helper_type == "build_carousel") {

# Build an array representing the currently-defined slides, their order and
# the files to which they refer
# 
# $slides['slide_title' => $slide_title, 'slide_file_extension' => $slide_file_extension]
#        

    build_slides_array_from_files();

    $website_title = get_website_title();

    $return = "
                <div id = 'home' class = 'container-fluid' style = 'margin-top: 2vh; margin-bottom: 4vh; width: 100%; text-align: center; background: Aquamarine;'>
                    <h4 style='padding: 1.5vh;' id = 'websitetitle' >$website_title</h4>
                </div>";

# Build the html representing 'carousel_inner'

    foreach ($slides as $i => $value) {

        $slide_title = $slides[$i]['slide_title'];
        $slide_file_extension = $slides[$i]['slide_file_extension'];

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
    }

    echo $return;
}

#####################  build_sections_view_table ####################

if ($helper_type == "build_sections_view_table") {

# Get a 2d array representing the currently-defined sections, their order and the 
# entries within them:
# 
# $sections['section_id' => $section_id, 'section_header' => $section_header, 'section_type' => $section_type', 'section_prefix' => $section_prefix
#        entries['entry_title' => $entry_title, 'entry_suffix' => $entry_suffix]]
#        
# Where $section_type indicate whether the title is of the form 'date_title' (value 'date_title')
# or simply 'title' (value 'standard_title')
# 

    build_sections_array_from_files();

    $return = "";

    for ($i = 0; $i < count($sections); $i++) {

        $section_id = $sections[$i]['section_id'];
        $section_header = $sections[$i]['section_header'];
        $section_type = $sections[$i]['section_type'];
        $section_prefix = $sections[$i]['section_prefix'];

        $return .= "<p class='sectionheader'>$section_header</p>";

        $count = 0;
        $entriesa = "<div id = '" . $section_id . "a' style = 'display: block;'>";
        $entriesb = "<div id = '" . $section_id . "b' style = 'display: none;'>";

        $entries = $sections[$i]['entries'];

        if ($section_type == "standard_title") {
            $width = '80%';
        } else {
            $width = '60%';
        }

        foreach ($entries as $key => $value) {

# Build the html representing the entreis for  section as a pair of divs - the first ('section_id'entriesa') containing
# just the first four followed by a "more" button, the second (section_id'entriesb') containing all the entreis followed
# by a "less button"

            if ($section_type == "standard_title") {
                $entry_displaya = $section_prefix . "&nbsp;" . $entries[$key]['entry_title'];
                $entry_link = $section_id . "_" . $entries[$key]['entry_title'] . ".pdf";
            } else {
                $entry_displaya = $section_prefix . $entries[$key]['entry_date'];
                $entry_displayb = $entries[$key]['entry_suffix'];
                $entry_link = $section_id . "_" . $entries[$key]['entry_date'] . "_" . $entries[$key]['entry_suffix'] . ".pdf";
            }
            
            // $entry_standard below is the normal aquamarine form of a section entry, $entry_special
            // is a white-smoke version to signal the presence of the "more" button below. Previously
            // used opackty but this upset accessibility score

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
                if (count($entries) > 4) {
                    $entriesa .= $entry_special;
                    $entriesa .= "<p tabindex = '0' role = 'button' aria-label='Display all entries in this section'   
                                    onclick = 'togglesubdiv(\"$section_id\", event)' onkeydown = 'togglesubdiv(\"$section_id, event)'style='cursor: pointer;'>
                                    More <img src = 'img/caret-bottom.svg' alt='caret-bottom symbol'>
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
                        Less <img src = 'img/caret-top.svg' alt='caret-top symbol'>
                    </p>
                    </div>";

        $return .= $entriesa;
        $return .= $entriesb;
    }

    echo $return;
}