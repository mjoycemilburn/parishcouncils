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
// 'build_section_picklist'              -   return picklist html for given section_id
//
// 'build_sections_update_table'         -   create/edit/delete/reorder sections
//
// 'insert_section'                      -   insert a new section
//
// 'update_section'                      -   update section for given index - potentially change any or all
//                                           of section_id, section_header and section_type
//
// 'delete_section'                      -   delete the specified section
//
// 'reorder_sections'                    -   reorder the sections_configuration.txt file from the screen Dom
//
// 'build_carousel_update_table'           -   create/edit/delete/reorder carousel slides
//
// 'insert_slide'                        -   insert a new sllide
//
// 'update_slide'                        -   edit the title for the given slide
//
// 'delete_slide'                        -   delete the given slides
//
// 'reorder_slides'                      -   reorder the slides_configuration.txt file from the screen Dom
//
// 'build_entries_update_table'          -   build html to create/edit/delete/reorder entries for the given
//                                           section
//
// 'insert_entry'                        -   insert a new entry
//
// 'update_entry'                        -   edit an existing entry
//
// 'delete_entry'                        -   delete the given entry
//
// require "../../apparch_library/phpincludes/prepareStringforSQL.php";
// require "../../apparch_library/phpincludes/prepareStringforXMLandJSONParse.php";

$page_title = 'manager_helpers';

date_default_timezone_set('Europe/London');

// check logged_in

session_start();

if (!isset($_SESSION['council_id_for_logged_in_user'])) {
    echo "%timed_out%";
    exit(0);
}

$council_id = $_SESSION['council_id_for_logged_in_user'];
$council_name = $_SESSION['council_name_for_logged_in_user'];
$council_root_directory = $_SESSION['council_root_directory'];

// connect to the parishcouncils database

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

//////////////////////////////////////////  build_section_picklist ////////////////////////////////////////

if ($helper_type == "build_section_picklist") {
    $sql = "SELECT
                section_id
            FROM
                sections
            WHERE
                $council_id = '$council_id';";

    $result = sql_result_for_location($sql, 1);

    $return = "
        <label for ='sectionspicklist'
            title = 'Select the section for the Entry you want to manage'>
            Section :
        </label>
        <select id='sectionspicklist'
            onchange = 'currentSectionId = sectionspicklist.options[sectionspicklist.selectedIndex].value;
                        displayEntriesUpdateView(currentSectionId, \" \");'>";

    $first_row = true;
    while ($row = mysqli_fetch_array($result)) {
        $section_id = $row['section_id'];

        if ($first_row) {
            $return .= "<option selected value = '$section_id'>$section_id</option>";
            $first_row = false;
        } else {
            $return .= "<option value = '$section_id'>$section_id</option>";
        }
    }

    $return .= "</select>";

    echo $return;
}

//////////////////////////////////////////  build_sections_update_table ////////////////////////////////////////

if ($helper_type == "build_sections_update_table") {

// The html for the update and insert sections generated below look a bit complicated
    // because we're using sortableJS to implement the "drag and drop" mechanism used
    // to manage section order. SortableJS is a javascript library downloaded from Github.
    // See https://www.solodev.com/blog/web-design/how-to-create-sortable-lists-with-sortablejs.stml for example

    $return = "
        <h2 style='text-align: center;'>Configure Sections</h2>
        <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>";

    // first create a section insert block

    $return .= "
        <div class='container'>
            <div class='row justify-content-center mt-3 mb-3 pt-2 pb-2' style='background: white;'>
                <form>
                    <label for = 'sectionid'>&nbsp;&nbsp;Section id :&nbsp;</label>
                    <input type='text' maxlength='10' size='8' id = 'sectionid'
                        autocomplete='off' value = ''
                        title='Enter a short tag for the section - eg finstats'
                        onmousedown='clearMessageArea();'>
                    <label for = 'sectionheader'>&nbsp;&nbsp;Section header :&nbsp;</label>
                    <input type='text' maxlength='40' size='15' id = 'sectionheader'
                        autocomplete='off' value = ''
                        title='Enter a heading for this section - eg Financial Statements and Accounts'
                        onmousedown='clearMessageArea();'>
                    <label for = 'sectionprefix'>&nbsp;&nbsp;Section prefix :&nbsp;</label>
                    <input type='text' maxlength='20' size='10' id='sectionprefix'
                        autocomplete='off' disabled
                        title = 'For \"date-type\" sections enter the prefix for this section (if any) - eg \"Minutes for : \"'
                        onmousedown='clearMessageArea();'>
                    <label for ='sectiontypedate'>&nbsp;&nbsp;Date type&nbsp;</label>
                    <input type = radio id = 'sectiontypedate' name = 'section_type'
                       value = 'date_title'
                       title= 'check this button to include a date in section entry titles'
                        onclick='clearMessageArea(); togglePrefixStatus(\"\");'>
                    <label for ='sectiontypestandard'>&nbsp;&nbsp;Standard type&nbsp;</label>
                    <input type = radio id = 'sectiontypestandard' name = 'section_type'
                       value = 'standard_title' checked
                       title= 'check this button if you do not need dates in section entry titles'
                        onclick='clearMessageArea(); togglePrefixStatus(\"\");'>
                    <button id = 'insertbutton'  type='button' class='ml-2 btn-sm btn-primary'
                        title='Insert a new section'
                        onclick='insertSection();'>Insert
                    </button>
                </form>
            </div>
        </div>";

    // now create an update row for each currently-defined section

    $sql = "SELECT
            section_id,
            section_header,
            section_type,
            section_prefix,
            section_sequence_number
        FROM
            sections
        WHERE
            council_id ='$council_id'
        ORDER BY section_sequence_number ASC";

    $result = sql_result_for_location($sql, 2);

    $return .= "
        <div class='container'>
            <div id='sectionssortablelist' class='list-group'>";

    $i= 0;
    while ($row = mysqli_fetch_array($result)) {
        $section_id = $row['section_id'];
        $section_header = $row['section_header'];
        $section_type = $row['section_type'];
        $section_prefix = $row['section_prefix'];

        // Note we put entries in a form, even though we don't strictly need to (no file inputs)
        // but without this labels don't get vertically aligned properly. Note also we  create
        // a hidden "$id" element with a class of "sectionentry" and innerhtml $i. This makes it
        //  easy to count the sections if we have to "reorder" and supplies the original index

        $return .= "
                <div class='row justify-content-center pt-2 pb-2' style='background: white;'>
                    <div class='list-group-item mb-0 pb-0'>
                        <form>
                            <span id = 'section$i' class = 'sectionentry' style = 'display: none;'>$section_id</span>
                            <label for = 'sectionid$i'>&nbsp;&nbsp;Id :&nbsp;</label>
                            <input type='text' maxlength='10' size='8' id = 'sectionid$i'
                                autocomplete='off'
                                value = '$section_id'
                                placeholder = '$section_id'
                                title='Enter a short tag for the section - eg finstats'
                                onmousedown='clearMessageArea();'>
                            <label for ='sectionheader$i'>&nbsp;&nbsp;Header :&nbsp;</label>
                            <input type='text' maxlength='30' size='15' id='sectionheader$i'
                                autocomplete='off' value='$section_header'
                                title = 'Enter the Section Header - eg Financial Statements and Account'
                                onmousedown='clearMessageArea();'>";

        if ($section_type == 'date_title') {
            $return .= "
                            <label for = 'sectionprefix$i'>&nbsp;&nbsp;Prefix :&nbsp;</label>
                            <input type='text' maxlength='30' size='10' id='sectionprefix$i'
                                autocomplete='off' value = '$section_prefix'
                                title = 'For \"date-type\" sections enter the prefix for this section (if any) - eg \"Minutes for : \"'
                                onmousedown='clearMessageArea();'>
                            <label for ='sectiontypedate$i'>&nbsp;&nbsp;Date type&nbsp;</label>
                            <input type = radio id = 'sectiontypedate$i' name = 'sectiontype$i'
                                   value = 'date_title' checked
                                   title= 'check this button to include a date in section entry titles'
                                   onclick =  'clearMessageArea(); saveTypeChanged = true; togglePrefixStatus($i);'>
                            <label for ='standardtypedate$i'>&nbsp;&nbsp;Standard type&nbsp;</label>
                            <input type = radio id = 'sectiontypestandard$i' name = 'sectiontype$i'
                                   value = 'standard_title'
                                   title= 'check this button if you do not need dates in section entry titles'
                                   onclick =  'clearMessageArea(); saveTypeChanged = true; togglePrefixStatus($i);'>";
        } else {
            $return .= "
                            <label for = 'sectionprefix$i'>&nbsp;&nbsp;Prefix :&nbsp;</label>
                            <input type='text' maxlength='30' size='10' id='sectionprefix$i'
                                autocomplete='off' disabled
                                title = 'For \"date-type\" sections enter the prefix for this section (if any) - eg \"Minutes for : \"'
                                onclick='clearMessageArea();'>
                            <label for ='sectiontypedate$i'>&nbsp;&nbsp;Date type&nbsp;</label>
                            <input type = radio id = 'sectiontypedate$i' name = 'sectiontype$i'
                                   value = 'date_title'
                                   title= 'check this button to include a date in section entry titles'
                                   onclick =  'clearMessageArea(); saveTypeChanged = true; togglePrefixStatus($i);'>
                            <label for ='standardtypedate$i'>&nbsp;&nbsp;Standard type&nbsp;</label>
                            <input type = radio id = 'sectiontypestandard$i' name = 'sectiontype$i'
                                   value = 'standard_title' checked
                                   title= 'check this button if you do not need dates in section entry titles'
                                   onclick =  'clearMessageArea(); saveTypeChanged = true; togglePrefixStatus($i);'>";
        }
        $return .= "
                            <button id = 'updatebutton$i'  type='button' class='ml-2 mr-2 btn-sm btn-primary'
                                title='Update the section'
                                onmousedown='updateSection($i);'>Update
                            </button>
                            <button id = 'deletebutton$i'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                                title='Delete the section'
                                onmousedown='deleteSection($i);'>Delete
                            </button>
                        </form>
                    </div>
                </div>";

        $i++;
    }

    // add a final button to allow the sections array to be re-ordered

    $return .= "
            </div>
            <div style = 'text-align: center;'>
                <button id = 'reorderbutton'  type='button' class='mt-3 mr-2 btn-sm btn-primary'
                    title='Re-order the sections after \"drag and drop\"'
                    onclick='reorderSections();'>Reorder
                </button>
            </div>
        </div>";

    echo $return;
}

//////////////////////////////////////////  insert_section ////////////////////////////////////////

if ($helper_type == "insert_section") {
    $section_id = $_POST['section_id'];
    $section_header = $_POST['section_header'];
    $section_type = $_POST['section_type'];
    $section_prefix = $_POST['section_prefix'];

    // check that the section doesn't already exist

    $sql = "SELECT * FROM sections
            WHERE
                section_id = '$section_id' AND
                council_id ='$council_id';";

    $result = sql_result_for_location($sql, 3);

    if (mysqli_num_rows($result) >= 1) {
        echo "Oops - a section already exists with this id";
        exit(0);
    }

    // add the row to sections;

    $sql = "INSERT INTO sections (
                section_id,
                section_header,
                section_type,
                section_prefix,
                council_id)
            VALUES (
                '$section_id',
                '$section_header',
                '$section_type',
                '$section_prefix',
                '$council_id');";

    $result = sql_result_for_location($sql, 4);
}

//////////////////////////////////////////  update_section ////////////////////////////////////////

if ($helper_type == "update_section") {
    $section_id_old = $_POST['section_id_old'];
    $section_id_new = $_POST['section_id_new'];
    $section_header = $_POST['section_header'];
    $section_prefix = $_POST['section_prefix'];
    $section_type_new = $_POST['section_type_new'];

    // get the existing representation of the sections

    $sections = array();

    $sql = "SELECT * FROM sections
            WHERE
                council_id ='$council_id'
            ORDER BY
                section_sequence_number ASC;";

    $result = sql_result_for_location($sql, 5);

    while ($row = mysqli_fetch_array($result)) {
        $section[] = array(
            'section_id' => $row['section_id'],
            'section_header' => $row['section_header'],
            'section_type' => $row['section_type'],
            'section_prefix' => $row['section_prefix']);

        if ($row['section_id'] === $section_id_old) {
            $section_type_old = $row['section_type'];
        }
    }

    // if the section_id is changing, check that section_id_new is unique

    if ($section_id_new != $section_id_old) {
        $section_id_new_is_unique = true;

        for ($i = 0; $i < count($sections); $i++) {
            if ($section_id_new == $sections[$i]['section_id']) {
                $section_id_new_is_unique = false;
                echo "Oops - new section_id is not unique";
                exit(0);
            }
        }
    }

    // OK  - update the database

    $result = sql_result_for_location('START TRANSACTION', 6); // sql failure after this point will initiate rollback

    $sql = "UPDATE sections SET
                section_id  = '$section_id_new',
                section_header  = '$section_header',
                section_type  = '$section_type_new',
                section_prefix  = '$section_prefix'
            WHERE
                council_id = '$council_id' AND
                section_id  = '$section_id_old';";

    $result = sql_result_for_location($sql, 7);

    // get the existing representation of the entries in the section

    $entries = array();

    $sql = "SELECT * FROM entries
            WHERE
                section_id = '$section_id_old' AND
                council_id ='$council_id';";

    $result = sql_result_for_location($sql, 8);

    while ($row = mysqli_fetch_array($result)) {
        $entries[] = array(
            'entry_date' => $row['entry_date'],
            'entry_title' => $row['entry_title'],
            'section_id' => $row['section_id'],
            'council_id' => $row['council_id'],
            'entry_suffix' => $row['entry_suffix']);
    }

    // for date entries filenames are of the form :
    //          section_id _ entry_date _ suffix
    // for standard entries, filename are of the form :
    //          section_id _ entry_title

    // if the section name has changed update the name of every entry in the section

    if ($section_id_new != $section_id_old) {
        $sql = "UPDATE entries SET
                        section_id  = '$section_id_new'
                    WHERE
                        council_id = '$council_id' AND
                        section_id = '$section_id_old';";

        $result = sql_result_for_location($sql, 9);

        if ($files = glob("$council_root_directory/entries/$section_id_old" . "_*.*")) {
            for ($i = 0; $i < count($files); $i++) {
                $current_filename = basename($files[$i]);

                $temp = ltrim($current_filename, $section_id_old);
                $new_filename = $section_id_new . $temp;
                if (!rename("$council_root_directory/entries/$current_filename", "$council_root_directory/entries/$new_filename")) {
                    echo "Oops! rename %failed% in update_section for file $current_filename.";
                    exit(1);
                }
            }
        }
    }

    // if the section_type has changed, update the name of every entry in the section wrt new type

    if ($section_type_new !== $section_type_old) {
        if ($section_type_old === "standard_title") {
            $dummy_date = "1970-01-01";
            foreach ($entries as $key => $value) {

                // add a dummy  1970-01-01 (onward) date and blank suffix to every entry for this section

                $entry_title_old = $entries[$key]['entry_title'];

                // ideally we'd blank out the entry_title fields in the entries for this section, but we can't, firstly because
                // they're a date and secondly because they're a key and therefore need to be unique - rats! They won't
                // be used though, so add a dummy $key value. Blank the suffices though

                $sql = "UPDATE entries SET
                            entry_date  = '$dummy_date',
                            entry_title = '$key',
                            entry_suffix = ''
                        WHERE
                            entry_title = '$entry_title_old' AND
                            section_id = '$section_id_new' AND
                            council_id = '$council_id';";

                $result = sql_result_for_location($sql, 11);


                $existing_filename = $section_id_new . "_" . $entries[$key]['entry_title'] . ".pdf";
                $new_filename = $section_id_new . "_" . $dummy_date . "_" . '' . ".pdf";

                if (!rename("$council_root_directory/entries/$existing_filename", "$council_root_directory/entries/$new_filename")) {
                    echo "Oops! rename %failed% in update_section for file $existing_title.";
                    $result = sql_result_for_location('ROLLBACK', 12);
                    exit(1);
                }

                $dummy_date = date('Y-m-d', strtotime($dummy_date . ' + 1 days'));
            }
        } else {

            // replace date in the title for every entry in this section with an indexnumber

            $dummy_date = "1970-01-01";
            foreach ($entries as $key => $value) {
                $entry_date_old = $entries[$key]['entry_date'];

                // ideally we'd blank out the entry_date fields in the entries for this section, but we can't, firstly because
                // they're a date and secondly because they're a key and therefore need to be unique - rats! They won't
                // be used though, so add a dummy 1970-01-01 (onward) date.Blank the suffices though

                $entry_title_old = $entries[$key]['entry_title'];


                $sql = "UPDATE entries SET
                                entry_title  = '$key',
                                entry_date = '$dummy_date',
                                entry_suffix = ''
                            WHERE
                                entry_date = '$entry_date_old' AND
                                section_id  = '$section_id_new' AND
                                council_id = '$council_id';";

                $result = sql_result_for_location($sql, 13);

                $existing_filename = $section_id_new . "_" . $entries[$key]['entry_date'] . "_" . $entries[$key]['entry_suffix'] . ".pdf";
                $new_filename = $section_id_new . "_" . $key . ".pdf";

                if (!rename("$council_root_directory/entries/$existing_filename", "$council_root_directory/entries/$new_filename")) {
                    echo "Oops! rename %%failed%% in save_sections for file $existing_filename.";
                    $result = sql_result_for_location('ROLLBACK', 14);
                    exit(1);
                }

                $dummy_date = date('Y-m-d', strtotime($dummy_date . ' + 1 days'));
            }
        }
    }

    $result = sql_result_for_location('COMMIT', 15);
}

//////////////////////////////////////////  delete_section ////////////////////////////////////////

if ($helper_type == "delete_section") {
    $section_id = $_POST['section_id'];

    $result = sql_result_for_location('START TRANSACTION', 16); // sql failure after this point will initiate rollback

    $sql = "DELETE FROM sections WHERE
                section_id = '$section_id' AND
                council_id = '$council_id';";

    $result = sql_result_for_location($sql, 17);

    $sql = "DELETE FROM entries WHERE
                section_id = '$section_id' AND
                council_id = '$council_id';";

    $result = sql_result_for_location($sql, "17a");

    // delete any file entries for $section__id

    $folder_target = "$council_root_directory/entries";

    if ($files = glob("$folder_target/$section_id" . "_*.*")) {
        for ($j = 0; $j < count($files); $j++) {
            if (!unlink($files[$j])) {
                echo "Oops! unlink %%failed%% in delete_sections.";
                $result = sql_result_for_location('ROLLBACK', 18);
                exit(1);
            }
        }
    }

    $result = sql_result_for_location('COMMIT', 23);
}

//////////////////////////////////////////  reorder_sections ////////////////////////////////////////

if ($helper_type == "reorder_sections") {
    $sequenced_sections_json = $_POST['sequenced_sections_json'];

    // convert this to a sections array

    $sections = json_decode($sequenced_sections_json, true);

    for ($i = 0; $i < count($sections); $i++) {
        $sql = "UPDATE sections
            SET
                section_sequence_number = '$i'
            WHERE
                section_id = '$sections[$i]' AND
                council_id = '$council_id';";

        $result = sql_result_for_location($sql, 24);
    }
}

//////////////////////////////////////////  build_carousel_update_table ////////////////////////////////////////

if ($helper_type == "build_carousel_update_table") {
    $return = "
        <h2 style='text-align: center;'>Configure Carousel</h2>
        <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>";

    // first create a slide insert block

    $return .= "
        <div class='container'>
            <div class='row justify-content-center mt-3 mb-3 pt-2 pb-2' style='background: white;'>
                <form enctype='multipart/form-data' method='post' name='slideform'>
                    <label for = 'slidetitle'>&nbsp;&nbsp;Slide title :&nbsp;</label>
                    <input type='text' maxlength='60' size='25' id = 'slidetitle'
                        autocomplete='off' value = ''
                        title='Enter a title for the slide - eg \"Newbiggin by moonlight\"'
                        onmousedown='clearMessageArea();'>
                    <label for ='slidefilename'>&nbsp;&nbsp;Filename :&nbsp;</label>
                    <input type='file' id='slidefilename' name='slidefilename'
                        accept='image/*'
                        title = 'Select a graphic-format file for this slide'
                        onmousedown='clearMessageArea();'>
                    <button id = 'insertbutton'  type='button' class='ml-2 btn-sm btn-primary'
                        title='Insert a new section'
                        onclick='insertSlide(\"slideform\");'>Insert
                    </button>
                </form>
            </div>
        </div>";

    // now create an update row for each currently-defined slide

    $sql = "SELECT
                slide_title
            FROM
                slides
            WHERE
                council_id ='$council_id'
            ORDER BY slide_sequence_number ASC";

    $result = sql_result_for_location($sql, 25);

    $return .= "
            <div class='container'>
                <div id='slidessortablelist' class='list-group'>";

    $i= 0;
    while ($row = mysqli_fetch_array($result)) {
        $slide_title = $row['slide_title'];

        $return .= "
                    <div class='row justify-content-center pt-2 pb-2' style='background: white;'>
                        <div class='list-group-item mb-0 pb-0'>
                            <form enctype='multipart/form-data' method='post' name='slideform$i'>
                                <span id = 'slide$i' class = 'slideentry' style = 'display: none;'>$slide_title</span>
                                <label for ='slidetitle$i'>&nbsp;&nbsp;Title :&nbsp;</label>
                                <input type='text' maxlength='60' size='15' id='slidetitle$i'
                                    autocomplete='off' value='$slide_title' placeholder='$slide_title'
                                    title = 'Enter an amended title for the slide'
                                    onmousedown='clearMessageArea();'>
                                <label for ='slidefilename$i'>&nbsp;&nbsp;Filename :&nbsp;</label>
                                <input type='file' id='slidefilename$i' name='slidefilename$i'
                                    accept='image/*'
                                    title = 'Select a graphic-format file to replace this slide'
                                    onmousedown='clearMessageArea();'>
                                <button id = 'updatebutton$i'  type='button' class='ml-2 mr-2 btn-sm btn-primary'
                                    title='Update the Slide'
                                    onmousedown='updateSlide($i);'>Update
                                </button>
                                <button id = 'deletebutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                                    title='Delete the slide'
                                    onmousedown='deleteSlide($i);'>Delete
                                </button>
                            </form>
                        </div>
                    </div>";

        $i++;
    }

    // add a final button to allow the slides array to be re-ordered

    $return .= "
            </div>
            <div style = 'text-align: center;'>
                <button id = 'reorderbutton'  type='button' class='mt-3 mr-2 btn-sm btn-primary'
                    title='Re-order the slides after \"drag and drop\"'
                    onclick='reorderSlides();'>Reorder
                </button>
            </div>
        </div>";

    echo $return;
}

//////////////////////////////////////////  insert_slide ////////////////////////////////////////

if ($helper_type == "insert_slide") {
    $slide_title = $_POST['slide_title'];

    $slides = array();

    $sql = "SELECT * FROM slides
            WHERE
                council_id ='$council_id'
            ORDER BY
                slide_sequence_number ASC;";

    $result = sql_result_for_location($sql, 26);

    while ($row = mysqli_fetch_array($result)) {
        $slides[] = array(
            'slide_title' => $row['slide_title'],
            'slide_file_extension' => $row['slide_file_extension']);
    }

    // check there's not already a slide with this title

    foreach ($slides as $key => $value) {
        if ($slides[$key]['slide_title'] === $slide_title) {
            echo "Oops - a slide already exists for this title";
            exit(0);
        }
    }

    // get the number of entries currently in the array as the sequence number of the new slide

    $slide_sequence_number = count($slides);

    // get the file name and type

    $slide_file_name = $_FILES["slidefilename"]["name"];
    $info = pathinfo($slide_file_name);
    $slide_file_extension = $info['extension'];

    $result = sql_result_for_location('START TRANSACTION', 27); // sql failure after this point will initiate rollback

    // add the row to $slides;

    $sql = "INSERT INTO slides (
                slide_title,
                slide_file_extension,
                slide_sequence_number,
                council_id)
            VALUES (
                '$slide_title',
                '$slide_file_extension',
                '$slide_sequence_number',
                '$council_id');";

    $result = sql_result_for_location($sql, 28);

    // and upload the slide

    $upload_target = $council_root_directory . "/slides/$slide_title.$slide_file_extension";

    if (move_uploaded_file($_FILES['slidefilename'] ['tmp_name'], $upload_target)) {
        echo "Slide successfully uploaded";
    } else {
        echo "Oops - slide upload failed";
        $result = sql_result_for_location('ROLLBACK', 29);
        exit(1);
    }

    $result = sql_result_for_location('COMMIT', 30);
}

//////////////////////////////////////////  update_slide ////////////////////////////////////////

if ($helper_type == "update_slide") {
    $slide_index = $_POST['slide_index'];
    $slide_title_new = $_POST['slide_title_new'];
    $slide_title_old = $_POST['slide_title_old'];
    $source_status = $_POST['source_status'];

    $new_title = false;
    if ($slide_title_new != $slide_title_old) {
        $new_title = true;
    }
    $new_file = false;
    if ($source_status == "new source file provided") {
        $new_file = true;
    }

    // We need to check that any new name for the current title doesn't clash with any existing
    // other title.

    if ($new_title) {
        $sql = "SELECT * FROM slides
            WHERE
                slide_title ='$slide_title_new' AND
                council_id ='$council_id';";

        $result = sql_result_for_location($sql, 31);

        if (mysqli_num_rows($result) >= 1) {
            echo "Oops - a slide with this title already exists";
            exit(0);
        }

        // update the database

        $result = sql_result_for_location('START TRANSACTION', 32); // sql failure after this point will initiate rollback

        $sql = "UPDATE slides
                SET
                    slide_title = '$slide_title_new'
                WHERE
                    slide_title = '$slide_title_old' AND
                    council_id = '$council_id';";

        $result = sql_result_for_location($sql, 33);
    }

    // now deal with the consequences for the slide files

    if ($new_file) {

        // delete the existing file for title (we don't need to know its extension) and upload the new file

        $files = glob("$council_root_directory/slides/$slide_title_old.*");
        unlink($files[0]); // there should only be one file and we don't mind if it doesn't get deleted

        $new_file_extension = pathinfo($_FILES['slidefilename' . $slide_index]["name"])['extension'];
        if ($new_title) {
            $target = "$slide_title_new";
        } else {
            $target = "$slide_title_old";
        }
        $upload_target = "$council_root_directory/slides/$target.$new_file_extension";

        if (move_uploaded_file($_FILES['slidefilename' . $slide_index] ['tmp_name'], $upload_target)) {
            echo "upload succeeded";
        } else {
            echo "upload %failed% - move_uploaded in update_slide";
            $result = sql_result_for_location('ROLLBACK', 34);
            exit(1);
        }
    } else {
        if ($new_title) {

            // get old file extension and rename the file

            $files = glob("$council_root_directory/slides/$slide_title_old.*");
            $old_slide_file_extension = pathinfo($files[0])['extension']; // there should only be one file

            $old_target = "$slide_title_old.$old_slide_file_extension";
            $new_target = "$slide_title_new.$old_slide_file_extension";
            $rename_old_target = "$council_root_directory/slides/$old_target";
            $rename_new_target = "$council_root_directory/slides/$new_target";

            if (rename($rename_old_target, $rename_new_target)) {
                echo "rename succeeded";
            } else {
                echo "rename %failed% - rename in update_slide";
                $result = sql_result_for_location('ROLLBACK', 35);
                exit(0);
            }
        } else {
            // nothing to do
        }
    }
    $result = sql_result_for_location('COMMIT', 36);
}

//////////////////////////////////////////  delete_slide ////////////////////////////////////////

if ($helper_type == "delete_slide") {
    $slide_title = $_POST['slide_title'];

    $result = sql_result_for_location('START TRANSACTION', 44); // sql failure after this point will initiate rollback

    $sql = "DELETE FROM slides WHERE
                slide_title = '$slide_title' AND
                council_id = '$council_id';";

    $result = sql_result_for_location($sql, 38);

    // delete the slide file itelf - we don't know the extension, so use glob again

    $slides = glob("$council_root_directory/slides/$slide_title.*");

    if (!unlink($slides[0])) {
        echo "Oops! unlink %%failed%% in delete_slide.";
        $result = sql_result_for_location('ROLLBACK', 39);
        exit(1);
    }

    $result = sql_result_for_location('COMMIT', 40);
}

//////////////////////////////////////////  reorder_slides ////////////////////////////////////////

if ($helper_type == "reorder_slides") {
    $sequenced_slides_json = $_POST['sequenced_slides_json'];

    // convert this to a slides array

    $slides = json_decode($sequenced_slides_json, true);

    for ($i = 0; $i < count($slides); $i++) {
        $sql = "UPDATE slides
                SET
                    slide_sequence_number = '$i'
                WHERE
                    slide_title = '$slides[$i]' AND
                    council_id = '$council_id';";

        $result = sql_result_for_location($sql, 41);
    }
}

//////////////////////////////////////////  build_entries_update_table ////////////////////////////////////////

if ($helper_type == "build_entries_update_table") {
    $section_id = $_POST['section_id'];

    $sql = "SELECT
                section_id,
                section_type,
                section_header,
                section_prefix
            FROM
                sections
            WHERE
                section_id = '$section_id' AND
                council_id ='$council_id';";

    $result = sql_result_for_location($sql, 42);

    $row = mysqli_fetch_array($result);

    $section_id = $row['section_id'];
    $section_type = $row['section_type'];
    $section_header = $row['section_header'];
    $section_prefix = $row['section_prefix'];

    $today = date("Y-m-d");

    // include a hidden span in the heading to return section_type to manager.html

    $return = "
        <h2 style='text-align: center;'>Manage Entries for the \"$section_id\" Section</h2>
            <span id='currentsectiontype' style='display: none;'>$section_type</span>
            <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>";

    // Build an "insert" row for the section

    $return .= "
        <div class='container'>
            <div class='row justify-content-center mt-3 mb-3 pt-2 pb-2' style='background: white;'>
                <form enctype='multipart/form-data' method='post' name='entryform'>";


    if ($section_type == "date_title") {
        $return .= "
                    <label for ='entrydate'>&nbsp;&nbsp;$section_prefix</label>
                    <input type='text' maxlength='30' size='10' id='entrydate'
                        autocomplete='off' value=''
                        title = 'Enter the date for this entry'
                        onmousedown='clearMessageArea(); applyDatepicker(\"entrydate\");'>
                    <label for ='entrysuffix'>&nbsp;&nbsp;Suffix : </label>
                    <input type='text' maxlength='40' size='10' id='entrysuffix'
                        autocomplete='off' value=''
                        title = 'Enter the suffix for this entry (if any)'
                        onmousedown='clearMessageArea();'>";
    } else {
        $return .= "
                    <label for ='entrydate'>&nbsp;&nbsp;Entry Title : </label>
                    <input type='text' maxlength='40' size='20' id='entrytitle'
                        autocomplete='off' value=''
                        title = 'Enter the title for this entry'
                        onmousedown='clearMessageArea()'>";
    }

    $return .= "
                    <label for ='filename'>&nbsp;&nbsp;Filename : </label>
                    <input type='file' id='entryfilename' name='entryfilename'
                        accept='application/pdf'
                        title = 'Select a pdf file for this entry'
                        onmousedown='clearMessageArea();'>
                    <button type='button' id='insertbutton'
                        class='mr-2 btn-sm btn-primary'
                        title='Insert this entry'
                        onclick = 'insertEntry();'>Insert
                    </button>
                </form>
            </div>
        </div>";

    // Now build "update" rows for each of the entries currently defined for the section

    $sql = "SELECT
                entry_title,
                entry_date,
                entry_suffix
            FROM
                entries
            WHERE
                section_id = '$section_id' AND
                council_id ='$council_id';";

    $result = sql_result_for_location($sql, 43);

    $return .= "
        <div class='container'>";

    $i = 0;

    while ($row = mysqli_fetch_array($result)) {
        $i++;

        $entry_title = $row['entry_title'];
        $entry_suffix = $row['entry_suffix'];
        $entry_date = $row['entry_date'];

        if ($section_type == "date_title") {
            $entry_filename = $section_id . "_" . $entry_date . "_" . $entry_suffix . ".pdf";
        } else {
            $entry_filename = $section_id . "_" . $entry_title . ".pdf";
        }

        $return .= "
            <div class='row justify-content-center pt-2 pb-2' style='background: white;'>
                <form enctype='multipart/form-data' method='post' name='entryform$i'>
                    <button type='button'
                        class='btn-sm btn-primary'
                        title='Preview the file currently linked to this entry'
                        onmousedown='clearMessageArea(); openFile(\"$entry_filename\");'>Preview
                    </button>
                    <label for ='entrydate$i'>&nbsp;&nbsp;$section_prefix</label>";

        if ($section_type == "date_title") {
            $return .= "
                    <input type='text' maxlength='10' size='10' id='entrydate$i'
                        autocomplete='off' value = '$entry_date' placeholder = '$entry_date'
                        title = 'Enter the date for this entry'
                        onmousedown='clearMessageArea(); applyDatepicker(\"entrydate$i\");'>
                    <label for ='entrysuffix$i'>&nbsp;&nbsp;Suffix : </label>
                    <input type='text' maxlength='40' size='10' id='entrysuffix$i'
                        value = '$entry_suffix' placeholder = '$entry_suffix'
                        autocomplete='off'
                        title = 'Enter the suffix for this entry (if any)'
                        onmousedown='clearMessageArea();'>";
        } else {
            $return .= "
                    <input type='text' maxlength='40' size='20' id='entrytitle$i'
                        autocomplete='off' value = '$entry_title' placeholder = '$entry_title'
                        title = 'Enter the title for this entry'
                        onmousedown='clearMessageArea();'>";
        }

        $return .= "
                    <label for ='entryfilename$i'>&nbsp;&nbsp;Filename : </label>
                    <input type='file' id='entryfilename$i' name='entryfilename$i'
                        accept='application/pdf'
                        title = 'Select a pdf file for this entry'
                        onmousedown='clearMessageArea();'>
                    <button type='button'
                        class='mr-2 btn-sm btn-primary'
                        title='Update this entry'
                        onclick='updateEntry($i);'>Update
                    </button>
                    <button type='button'
                        class='btn-sm btn-primary'
                        title='Delete this entry'
                        onclick='deleteEntry($i);'>Delete
                    </button>
                </form>
            </div>";
    }

    $return .= "
        </div>";

    echo $return;
}

//////////////////////////////////////////  insert_entry ////////////////////////////////////////

if ($helper_type == "insert_entry") {
    $section_id = $_POST['section_id'];
    $section_type = $_POST['section_type'];

    if ($section_type == "date_title") {
        $entry_title = '';
        $entry_date = $_POST['entry_date'];
        $entry_suffix = $_POST['entry_suffix'];
        $filename = $section_id . "_" . $entry_date . "_";
        if ($entry_suffix != '') {
            $filename .= $entry_suffix;
        }
    } else {
        $entry_date = '1970-01-01';
        $entry_suffix = '';
        $entry_title = $_POST['entry_title'];
        $filename = $section_id . "_" . $entry_title;
    }

    $filename .= ".pdf";
    $folder_target = $council_root_directory . "/entries";

    // check the database to see that the entry key (either section_id/entry_date or section_id/entry_title
    // depending on section_type) is unique

    if (!unique_entry_key($council_id, $section_id, $section_type, $entry_date, $entry_title)) {
        if ($section_type === "date_title") {
            echo "Oops - date needs to be unique for this section";
            exit(0);
        } else {
            echo "Oops - title needs to be unique for this section";
            exit(0);
        }
    }

    // OK - all good  - try to update the database and upload the file

    $result = sql_result_for_location('START TRANSACTION', 44); // sql failure after this point will initiate rollback

    $sql = "INSERT INTO entries (
                    entry_date,
                    entry_suffix,
                    entry_title,
                    section_id,
                    council_id)
                VALUES (
                    '$entry_date',
                    '$entry_suffix',
                    '$entry_title',
                    '$section_id',
                    '$council_id');";

    $result = sql_result_for_location($sql, 45);

    $upload_target = "$folder_target/$filename";

    if (move_uploaded_file($_FILES['entryfilename'] ['tmp_name'], $upload_target)) {
        echo "insert succeeded";
    } else {
        echo "insert %failed% - the file could not be uploaded in insert_entry";
        $result = sql_result_for_location('ROLLBACK', 46);
        exit(1);
    }

    $result = sql_result_for_location('COMMIT', 47);
}

//////////////////////////////////////////  update_entry ////////////////////////////////////////

if ($helper_type == "update_entry") {
    $entry_index = $_POST['entry_index'];
    $section_id = $_POST['section_id'];
    $section_type = $_POST['section_type'];

    if ($section_type == "date_title") {
        $entry_date = $_POST['entry_date'];
        $entry_suffix = $_POST['entry_suffix'];
        $entry_title = '';  // placeholder
        $new_filename = $section_id . "_" . $entry_date . "_" . $entry_suffix;
        $new_key = $section_id . "_" . $entry_date;
        $entry_date_old = $_POST['entry_date_old'];
        $entry_suffix_old = $_POST['entry_suffix_old'];
        $current_filename = $section_id . "_" . $entry_date_old . "_" . $entry_suffix_old;
        $current_key = $section_id . "_" . $entry_date_old;
    } else {
        $entry_title = $_POST['entry_title'];
        $entry_date = '1970-01-01'; //placeholder
        $entry_sufffix = ''; //placeholder
        $new_filename = $section_id . "_" . $entry_title;
        $new_key = $section_id . "_" . $entry_title;
        $entry_title_old = $_POST['entry_title_old'];
        $current_filename = $section_id . "_" . $entry_title_old;
        $current_key = $section_id . "_" . $entry_title_old;
    }
    $source_status = $_POST['source_status'];

    $new_filename .= ".pdf";
    $current_filename .= ".pdf";

    // if $new_filename differs from $current_filename then the database keys must have changed, so
    // check we still have a unique combination

    if ($new_key != $current_key) {
        if (!unique_entry_key($council_id, $section_id, $section_type, $entry_date, $entry_title)) {
            if ($section_type === "date_title") {
                echo "Oops - an entry already exists for this date";
                exit(0);
            } else {
                echo "Oops - an entry already exists for this title";
                exit(0);
            }
        }
    }

    // update the database

    $result = sql_result_for_location('START TRANSACTION', 48); // sql failure after this point will initiate rollback

    if ($section_type == "date_title") {
        $sql = "UPDATE entries
                    SET
                        entry_date = '$entry_date',
                        entry_suffix = '$entry_suffix'
                    WHERE
                        section_id = '$section_id' AND
                        entry_date = '$entry_date_old' AND
                        council_id = '$council_id';";
    } else {
        $sql = "UPDATE entries
                    SET
                        entry_title = '$entry_title'
                    WHERE
                        section_id = '$section_id' AND
                        entry_title = '$entry_title_old' AND
                        council_id = '$council_id';";
    }

    $result = sql_result_for_location($sql, 49);

    if ($new_filename != $current_filename) {
        if ($source_status == "new source file provided") {

            // changed filename and provided new content - rename old file and upload new content

            if (!rename("$council_root_directory/entries/$current_filename", "$council_root_directory/entries/$new_filename")) {
                echo "rename %failed% - rename in update_entry";
                $result = sql_result_for_location('ROLLBACK', 50);
                exit(1);
            }
            if (move_uploaded_file($_FILES['entryfilename' . $entry_index] ['tmp_name'], "$council_root_directory/entries/$new_filename")) {
                echo "upload succeeded";
            } else {
                echo "upload %failed% - move_uploaded in update_entry";
                $result = sql_result_for_location('ROLLBACK', 51);
                exit(1);
            }
        } else {

            // changed filename but no new content - rename old file

            if (rename("$council_root_directory/entries/$current_filename", "$council_root_directory/entries/$new_filename")) {
                echo "rename succeeded";
            } else {
                echo "rename %failed% - rename in update_entry";
                $result = sql_result_for_location('ROLLBACK', 52);
                exit(1);
            }
        }
    } else {
        if ($source_status == "new source file provided") {

            // filename the same but new content - upload new content
            if (move_uploaded_file($_FILES['entryfilename' . $entry_index] ['tmp_name'], "$council_root_directory/entries/$new_filename")) {
                echo "upload succeeded";
            } else {
                echo "upload %failed% - move_uploaded in update_entry";
                $result = sql_result_for_location('ROLLBACK', 53);
                exit(1);
            }
        } else {

        // keys the same and no new content - do nothing
        }
    }
    $result = sql_result_for_location('COMMIT', 54);
}

//////////////////////////////////////////  delete_entry ////////////////////////////////////////

if ($helper_type === "delete_entry") {
    $section_id = $_POST['section_id'];
    $section_type = $_POST['section_type'];
    $entry_date = $_POST['entry_date'];
    $entry_suffix = $_POST['entry_suffix'];
    $entry_title = $_POST['entry_title'];

    if ($section_type === "date_title") {
        $filename = $section_id . "_" . $entry_date . "_" . $entry_suffix . ".pdf";
    } else {
        $filename = $section_id . "_" . $entry_title . ".pdf";
    }

    // OK - update the database first

    $result = sql_result_for_location('START TRANSACTION', 48); // sql failure after this point will initiate rollback

    if ($section_type === 'date_title') {
        $sql = "DELETE FROM entries WHERE
                    section_id = '$section_id' AND
                    entry_date = '$entry_date' AND
                    entry_suffix = '$entry_suffix' AND
                    council_id ='$council_id';";
    } else {
        $sql = "DELETE FROM entries WHERE
                    section_id = '$section_id' AND
                    entry_title = '$entry_title' AND
                    council_id ='$council_id';";
    }

    $result = sql_result_for_location($sql, 17);

    // now delete the file

    if (!unlink("$council_root_directory/entries/$filename")) {
        echo "Oops! unlink %%failed%% in delete_entry.";
        $result = sql_result_for_location('ROLLBACK', 53);
        exit(1);
    } else {
        echo "file successfully deleted";
    }

    $result = sql_result_for_location('COMMIT', 54);
}

disconnect_from_database();
