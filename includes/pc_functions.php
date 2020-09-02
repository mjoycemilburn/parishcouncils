<?php

function build_sections_array_from_files() {

    require ('../includes/pc_globals.php');

// The $sections array has the following format
//
// ['section_id': aaaa, 'section_header': bbbbbbb, 'section_type': ccccc, 'section_prefix': ddddd , 'entries' array()]
// 
// typical values would be:
// 
// aaaa     'finstats'
// bbbb     'Financial Statements and Accounts'
// cccc     'date_title'
// dddd     'Minutes for : " or "Entry for : "
// 
// For "date_title" sections, the  entries array contains the following elements:
// 
// ['entry_date': yyyy-mm-dd, "entry_suffix': 'suffix string]
// 
// For "standard_title" sections, the entries array contains
// 
// ['entry_title': 'title string']
// 
// The $sections array is built in two stages. The first stage use the sections_configuration.txt
// file to build a $setions_precursor - ie a $sections file without the entries element. This is
// added in the second stage when we access the files directory to see what enitries have been 
// defined for each seection
// 
// get the json : see https://www.kodingmadesimple.com/2017/05/add-update-delete-read-json-file-php.html
// for background

    if ($sections_precursor_json = file_get_contents('../configurations/sections_configuration.txt')) {

// decode json to associative array
        $sections_precursor = json_decode($sections_precursor_json, true);
    } else {
        echo "Oops! JSON fetch %%failed%% in build_sections_array_from_files.";
        exit(1);
    }

// Use this to construct the sections associative array described in 
// build_entries_view_table. As you go, build a reference table linking
// section-ids to their slot in the sections array

    $sections = array();
    $sections_keys = array();
    $i = 0;

    foreach ($sections_precursor as $key => $value) {

        $sections[$i]['section_id'] = $sections_precursor[$key]['section_id'];
        $sections[$i]['section_header'] = $sections_precursor[$key]['section_header'];
        $sections[$i]['section_type'] = $sections_precursor[$key]['section_type'];
        $sections[$i]['section_prefix'] = $sections_precursor[$key]['section_prefix'];
        $sections[$i]['entries'] = array();

// tuck the sequence code for a section into an associative array section_keys'section_id": sequence_code
        $sections_keys[$sections_precursor[$key]['section_id']] = $i;


// Now add the entries themselves

        if ($files = scandir('../entries')) {

            // for a date_title section, sort descending (ie most recent first) , otherwise ascending

            if ($sections[$i]['section_type'] == 'date_title') {
                rsort($files, SORT_NATURAL | SORT_FLAG_CASE); // apply "natsort" natural, case-insensitive ascending sort-order          
            } else {
                sort($files, SORT_NATURAL | SORT_FLAG_CASE);
            }

// now work through the list and stick them into the entries table (by default,
// they're sorted into ascending alphabetical order. Ignore "." and ".." entries

            for ($j = 0; $j < count($files); $j++) {

// filename format is either :
// 
//  'section_id'_'entry_date'_'entry_suffix.pdf or
//  'section_id'_'entry_title.pdf
//
// trim off the .pdf and then separate the other bit by exploding on "_" (a forbidden
// character in both title and suffix)

                if ($files[$j] != "." && $files[$j] != "..") {

                    // remove the extension (.pdf)

                    $temp = substr($files[$j], 0, -4);
                    $pieces = explode("_", $temp);

                    if ($pieces[0] == $sections[$i]['section_id']) {

                        if ($sections[$i]['section_type'] == "date_title") {
                            if (count($pieces) == 2)
                                $pieces[2] = '';
                            $sections[$i]['entries'][] = array("entry_date" => $pieces[1], "entry_suffix" => $pieces[2]);
                        } else {
                            $sections[$i]['entries'][] = array("entry_title" => $pieces[1]);
                        }
                    }
                }
            }
        } else {
            echo "Oops! scandir %%failed%% in build_sections_array_from_files.";
            exit(1);
        }
        $i++;
    }
}

function rewrite_sections_configuration_file() {

    require ('../includes/pc_globals.php');

    $sections_precursor_json = json_encode($sections_precursor);

    if (file_put_contents('../configurations/sections_configuration.txt', $sections_precursor_json)) {
        return true;
    } else {
        return false;
    }
}

function build_slides_array_from_files() {

    require ('../includes/pc_globals.php');

// first get the titles of the slides - these are held as a json in a file called
// slides_configuration. The format of each slide in the file is as follows:
//
// 'slide_title': aaaa  
// 
// typical values would be:
// 
// aaaa     'Milburn from the air'
// 
// at this stage we refer to the associative array version of this as "slides_precursor"
// because it lack the "slide_file_extension" element that will be added when we build the full associative
// sides array


    if ($slides_precursor_json = file_get_contents('../configurations/slides_configuration.txt')) {

// decode json to associative array
        $slides_precursor = json_decode($slides_precursor_json, true);
    } else {
        echo "Oops! JSON fetch %%failed%% in build_slides_array_from_files.";
        exit(1);
    }

// now search the slides folder for the file associated with each slide
// and add both the file and its file extension to the slides array

    $slides = array();
    $i = 0;

    foreach ($slides_precursor as $key => $value) {

        $slide_title = $slides_precursor[$key]['slide_title'];

        // find the associated file

        if ($files = scandir('../slides')) {

            for ($j = 0; $j < count($files); $j++) {

                if ($files[$j] != "." && $files[$j] != "..") {

                    // get the filename bit

                    $file = $files[$j];
                    $info = pathinfo($files[$j]);
                    $file_name = $info['filename'];
                    $file_extension = $info['extension'];

                    if ($file_name == $slide_title) {
                        $slides[$i]['slide_title'] = $slide_title;
                        $slides[$i]['slide_file_extension'] = $file_extension;
                        break;
                    }
                }
            }
        } else {
            echo "Oops! scandir %%failed%% in build_slides_array_from_files.";
            exit(1);
        }
        $i++;
    }
}

function rewrite_slides_configuration_file() {

    require ('../includes/pc_globals.php');

    $slides_precursor_json = json_encode($slides_precursor);

    if (file_put_contents('../configurations/slides_configuration.txt', $slides_precursor_json)) {
        return true;
    } else {
        return false;
    }
}

function get_website_title() {
 
        if ($website_title = file_get_contents('../configurations/website_title.txt')) {
            return $website_title;
    } else {
        return '';
    }
}

function save_website_title ($website_title) {
    
    if (file_put_contents('../configurations/website_title.txt', $website_title)) {
        return true;
    } else {
        return false;
    } 
}

function pr($var) {
    print '<pre>';
    print_r($var);
    print '</pre>';
}
