<?php

require('../includes/pc_functions.php');

# As directed by helper_type :    
# 
# 'build_section_picklist'              -   return pickist html for the currently-defined sections                                                     
#
# 'build_sections_update_table'         -   create/edit/delete/reorder sections                                                            
#
# 'insert_section'                      -   insert a new section                                                           
#
# 'update_section'                      -   update section for given index - potentially change any or all
#                                           of section_id, section_header and section_type
#
# 'delete_section'                      -   delete the specified section
# 
# 'reorder_sections'                    -   reorder the sections_configuration.txt file from the screen Dom
# 
# 'build_carousel_update_table'           -   create/edit/delete/reorder carousel slides                                                           
#
# 'update_website_title'                -   update the website title
# 
# 'insert_slide'                        -   insert a new sllide                                                           
#
# 'update_slide'                        -   edit the title for the given slide
#
# 'delete_slide'                        -   delete the given slides
#
# 'reorder_slides'                      -   reorder the slides_configuration.txt file from the screen Dom
# 
# 'build_entries_update_table'          -   build html to create/edit/delete/reorder entries for the given
#                                           section                                                          
#
# 'insert_entry'                        -   insert a new entry                                                           
#
# 'update_entry'                        -   edit an existing entry
#
# 'delete_entry'                        -   delete the given entry
#                                                                                
# require "../../apparch_library/phpincludes/prepareStringforSQL.php";
# require "../../apparch_library/phpincludes/prepareStringforXMLandJSONParse.php";

$page_title = 'pcouncil_entries_database_helpers';

# set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/London');

// check logged_in

session_start();

if (!isset($_SESSION['pc_user_logged_in'])) {
    echo "%timed_out%";
    exit(0);
}

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  build_section_picklist ####################

if ($helper_type == "build_section_picklist") {

    build_sections_array_from_files();


// things get messy from here on in if the array_build function has returned an empty array - which
// is prefectly permissible if the user has just deleted the last section! To put things on an even 
// keel again, the answer seems to be to be to invoke a system-initialisation routine here that
// puts a "default" section into the (empty) sections_configuration file

    if (count($sections_precursor) == 0) {
        $sections_precursor[0] = array('section_id' => 'default', 'section_type' => 'standard_title', 'section_header' => 'default', 'section_prefix' => '');
        if (!rewrite_sections_configuration_file()) {
            echo "Oops!  rewrite %failed% in build_section_picklist.";
            exit(0);
        }
        $current_section_id = "default";
    } else {
        $current_section_id = $_POST['current_section_id'];
    }

    // the routine aims to return a <select> element listing the sections found in $sections_precursor
    // as selectable options. The option for the section passed in the current_section_id parameter is
    // expected to be pre-selected. This parameter in turn has been retrieved from pc local storage
    // and is expected to be the last section visited. But what if something has gone wrong and this
    // section is no longer valid. It seems a good idea to build a little resilience here. The code
    // below selects the first entry in $sections if the current_section_id parameter cannot be found
    // elsewhere in the array.

    $current_section_id_index = 0;
    for ($i = 0; $i < count($sections_precursor); $i++) {

        $section_id = $sections_precursor[$i]['section_id'];

        if ($section_id == $current_section_id) {
            $current_section_id_index = $i;
        }
    }

    $return = "
        <label for ='sectionspicklist'
            title = 'Select the section for the Entry you want to manage'>
            Section : 
        </label>
        <select id='sectionspicklist'  onchange = 'displayEntriesUpdateView(\"select\", \" \");'>";

    for ($i = 0; $i < count($sections_precursor); $i++) {

        $section_id = $sections_precursor[$i]['section_id'];

        if ($i == $current_section_id_index) {
            $return .= "<option selected value = '$section_id'>$section_id</option>";
        } else {
            $return .= "<option value = '$section_id'>$section_id</option>";
        }
    }

    $return .= "</select>";

    echo $return;
}

#####################  build_sections_update_table ####################

if ($helper_type == "build_sections_update_table") {

    build_sections_array_from_files();


// The html for the update and insert sections generated below look a bit complicated
// because we're using sortableJS to implement the "drag and drop" mechanism used
// to manage section order. SortableJS is a javascript library downloaded from Github.
// See https://www.solodev.com/blog/web-design/how-to-create-sortable-lists-with-sortablejs.stml for example

    $return = "
        <h2 style='text-align: center;'>Configure Sections screen</h2>
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
                        title='Enter a header for this section - eg Financial Statements and Accounts'
                        onmousedown='clearMessageArea();'>
                    <label for = 'sectionprefix'>&nbsp;&nbsp;Section prefix :&nbsp;</label>
                    <input type='text' maxlength='20' size='10' id='sectionprefix' 
                        autocomplete='off' 
                        title = 'Enter the prefix for this entry (if any) - eg \"Minutes for : \"'
                        onmousedown='clearMessageArea();'>
                    <label for ='sectiontypedate'>&nbsp;&nbsp;Date type&nbsp;</label>
                    <input type = radio id = 'sectiontypedate' name = 'section_type'
                       value = 'date_title' 
                       title= 'check this button to include a date in section entry titles'
                        onmousedown='clearMessageArea();'>
                    <label for ='sectiontypestandard'>&nbsp;&nbsp;Standard type&nbsp;</label>
                    <input type = radio id = 'sectiontypestandard' name = 'section_type'
                       value = 'standard_title' checked
                       title= 'check this button if you do not need dates in section entry titles'
                        onmousedown='clearMessageArea();'>
                    <button id = 'insertbutton'  type='button' class='ml-2 btn-sm btn-primary'
                        title='Insert a new section'
                        onclick='insertSection();'>Insert
                    </button>
                </form>
            </div>
        </div>";

// now create an update row for each currently-defined section 

    $return .= "
        <div class='container'>
            <div id='sectionssortablelist' class='list-group'>";

    for ($i = 0; $i < count($sections); $i++) {

        $section_id = $sections[$i]['section_id'];
        $section_header = $sections[$i]['section_header'];
        $section_prefix = $sections[$i]['section_prefix'];

// Note we put entries in a form, even though we don'tstrictly need to (no file inputs)
// but without this labels don't get vertically aligned properly. Note also we  create
// a hidden "$id" element with a class of "sectionentry" and innerhtml $i. This makes it
//  easy to count the sextons if we have to "reorder" and supplies the original index

        $return .= "
                <div class='row justify-content-center pt-2 pb-2' style='background: white;'>
                    <div class='list-group-item mb-0 pb-0'>
                        <form>
                            <span id = 'section$i' class = 'sectionentry' style = 'display: none;'>$i</span> 
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
                                onmousedown='clearMessageArea();'>

                            <label for = 'sectionprefix$i'>&nbsp;&nbsp;Prefix :&nbsp;</label>
                            <input type='text' maxlength='30' size='10' id='sectionprefix$i' 
                                autocomplete='off' value = '$section_prefix'
                                title = 'Enter the prefix for entries in this section (if any)'
                                onmousedown='clearMessageArea();'>";

        if ($sections[$i]['section_type'] == 'date_title') {
            $return .= "
                            <label for ='sectiontypedate$i'>&nbsp;&nbsp;Date type&nbsp;</label> 
                            <input type = radio id = 'sectiontypedate$i' name = 'sectiontype$i'
                                   value = 'date_title' checked
                                   title= 'check this button to include a date in section entry titles'
                                   onmousedown =  'clearMessageArea(); saveTypeChanged = true;'>
                            <label for ='standardtypedate$i'>&nbsp;&nbsp;Standard type&nbsp;</label>
                            <input type = radio id = 'sectiontypestandard$i' name = 'sectiontype$i'
                                   value = 'standard_title'
                                   title= 'check this button if you do not need dates in section entry titles'
                                   onmousedown =  'clearMessageArea(); saveTypeChanged = true;'>";
        } else {
            $return .= "
                            <label for ='sectiontypedate$i'>&nbsp;&nbsp;Date type&nbsp;</label> 
                            <input type = radio id = 'sectiontypedate$i' name = 'sectiontype$i'
                                   value = 'date_title' 
                                   title= 'check this button to include a date in section entry titles'
                                   onmousedown =  'clearMessageArea(); saveTypeChanged = true;'>
                            <label for ='standardtypedate$i'>&nbsp;&nbsp;Standard type&nbsp;</label>
                            <input type = radio id = 'sectiontypestandard$i' name = 'sectiontype$i'
                                   value = 'standard_title' checked
                                   title= 'check this button if you do not need dates in section entry titles'
                                   onmousedown =  'clearMessageArea(); saveTypeChanged = true;'>";
        }
        $return .= "
                            <button id = 'updatebutton$i'  type='button' class='ml-2 mr-2 btn-sm btn-primary'
                                title='Update the section'
                                onmousedown='updateSection($i);'>Update
                            </button>
                            <button id = 'deletebutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                                title='Delete the section'
                                onmousedown='sectionIdofDeletionTarget = \"$section_id\"; deleteSection($i);'>Delete
                            </button>
                        </form>
                    </div>
                </div>";
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

#####################  insert_section ####################

if ($helper_type == "insert_section") {

    $section_id = $_POST['section_id'];
    $section_header = $_POST['section_header'];
    $section_type = $_POST['section_type'];
    $section_prefix = $_POST['section_prefix'];

// check that the section doesn't already exist

    build_sections_array_from_files();

    for ($i = 0; $i < count($sections); $i++) {
        if ($sections[$i]['section_id'] == $section_id) {
            echo "Oops - this section_id already exists";
            exit(1);
        }
    }

// add the new section to the sections arrays

    $sections[] = ['section_id' => $section_id,
        'section_header' => $section_header,
        'section_prefix' => $section_prefix,
        'section_type' => $section_type,
        'entries' => array()];

    $sections_precursor[] = ['section_id' => $section_id,
        'section_header' => $section_header,
        'section_prefix' => $section_prefix,
        'section_type' => $section_type];

    if (rewrite_sections_configuration_file()) {
        echo "Insert succeeded";
    } else {
        echo "Oops - insert failed";
    }
}

#####################  update_section ####################

if ($helper_type == "update_section") {

    $section_index = $_POST['section_index']; // this update is for the section_index'th entry in the current sections_config
    $section_id_old = $_POST['section_id_old'];
    $section_id_new = $_POST['section_id_new'];
    $section_header = $_POST['section_header'];
    $section_prefix = $_POST['section_prefix'];
    $section_type = $_POST['section_type'];


// get the existing representation of the sections

    build_sections_array_from_files();

    // if the section_id is changing, check that section_id_new is unique

    if ($section_id_new != $section_id_old) {

        $section_id_new_is_unique = true;

        for ($i = 0; $i < count($sections); $i++) {

            if ($i != $section_index && $section_id_new == $sections[$i]['section_id']) {
                $section_id_new_is_unique = false;
                echo "Oops - new section_id is not unique";
                exit(0);
            }
        }

        // OK , it's unique - update both $sections_precursor and $sections for for the section

        $sections_precursor[$section_index]['section_id'] = $section_id_new;
        $sections[$section_index]['section_id'] = $section_id_new;

        // now update the name of every file for the old section_id

        if ($files = scandir('../entries')) {

            for ($i = 0; $i < count($files); $i++) {

                $pieces = explode("_", $files[$i]);
                $entry_section_id = $pieces[0];

                if ($entry_section_id == $section_id_old) {

                    $current_filename = $files[$i];

                    $temp = ltrim($current_filename, $section_id_old);
                    $new_filename = $section_id_new . $temp;
                    if (!rename("../entries/$current_filename", "../entries/$new_filename")) {
                        echo "Oops! rename %failed% in update_section for file $current_filename.";
                        exit(1);
                    }
                }
            }
        } else {
            echo "Oops! scandir %%failed%% in update_section.";
        }
    }

// if section_type has changed for a section, transmit the consequences
// to the filenames of the associated entries

    $section_type_new = $section_type;
    $section_type_old = $sections[$section_index]['section_type'];

    if ($section_type_new != $section_type_old) {

        if ($section_type_old == "standard_title") {

// add a dummy date to every entry for this section - starting at 1970-01-01

            $dummy_date = "1970-01-01";

            foreach ($sections[$section_index]['entries'] as $key => $value) {

                $naked_title = $sections[$section_index]['entries'][$key]['entry_title'];

                $existing_title = $sections[$section_index]['section_id'] . "_" . $naked_title . ".pdf";
                $new_title = $sections[$section_index]['section_id'] . "_" . $dummy_date . "_" . $naked_title . ".pdf";

                if (!rename("../entries/$existing_title", "../entries/$new_title")) {
                    echo "Oops! rename %failed% in update_section for file $existing_title.";
                    exit(1);
                }

                $dummy_date = date('Y-m-d', strtotime($dummy_date . ' + 1 days'));
            }
        } else {

// replace date in the title for every entry in this section with an entry_index_number number

            $entry_index_number = 0;

            foreach ($sections[$section_index]['entries'] as $key => $value) {

// each entry is of the form 'yyyy-mm-dd_suffix

                $naked_date = $sections[$section_index]['entries'][$key]['entry_date'];
                $naked_suffix = $sections[$section_index]['entries'][$key]['entry_suffix'];

                $existing_filename = $sections[$section_index]['section_id'] . "_" . $naked_date . "_" . $naked_suffix . ".pdf";
                $new_filename = $sections[$section_index]['section_id'] . "_" . $entry_index_number . " " . $naked_suffix . ".pdf";

                if (!rename("../entries/$existing_filename", "../entries/$new_filename")) {
                    echo "Oops! rename %%failed%% in save_sections for file $existing_filename.";
                    exit(1);
                }

                $entry_index_number++;
            }
        }
    }

// update the sections entry in the current $sections_precursor

    $sections_precursor[$section_index]['section_header'] = $section_header;
    $sections_precursor[$section_index]['section_prefix'] = $section_prefix;
    $sections_precursor[$section_index]['section_type'] = $section_type;

    if (rewrite_sections_configuration_file()) {
        echo "Save succeeded";
    } else {
        echo "Oops - save failed";
    }
}

#####################  delete_section ####################

if ($helper_type == "delete_section") {

    $section_index = $_POST['section_index'];

// get the current $sections definitions

    build_sections_array_from_files();

// delete any file entries for the $section_index'th section_id

    if ($files = scandir('../entries')) {

        for ($j = 0; $j < count($files); $j++) {

// filename format is : 'section_code'_'entry_date'_'entry_title'. The _ separator
// between the fields is an illegal character within them so can use explode to 
// get the bits

            if ($files[$j] != "." && $files[$j] != "..") {

                $pieces = explode("_", $files[$j]);
                $entry_section_id = $pieces[0];

                if ($entry_section_id == $sections_precursor[$section_index]['section_id']) {

                    if (!unlink('../entries/' . $files[$j])) {
                        echo "Oops! unlink %%failed%% in delete_sections.";
                        exit(1);
                    }
                }
            }
        }
    } else {
        echo "Oops! scandir %%failed%% in delete_sections.";
    }

// remove the section_index'th entry

    unset($sections_precursor[$section_index]);

// there's a problem now as we want to encode the precursor befor writing to file, but since the
// keys aren't seqeuential now (in general at least), json_encode will encode to a JSON object rather
// than a JSON array. Drat. Pack the array.

    $temp = array();
    $j = 0;

    foreach ($sections_precursor as $key => $value) {
        $temp[$j] = $sections[$key];
        $j++;
    }

    $sections_precursor = $temp;

// overwrite the old json with the new one

    if (rewrite_sections_configuration_file()) {
        echo "Deletion succeeded";
    } else {
        echo "Oops - deletion failed";
    }
}

#####################  reorder_sections ####################

if ($helper_type == "reorder_sections") {

    $sections_precursor_json = $_POST['sections_precursor_json'];

// convert this to a sections_precursor array

    $sections_precursor = json_decode($sections_precursor_json, true);

// use this to overwrite the sections_configuration file

    if (rewrite_sections_configuration_file()) {
        echo "Reorder succeeded";
    } else {
        echo "Oops - reorder failed";
    }
}

#####################  build_carousel_update_table ####################

if ($helper_type == "build_carousel_update_table") {

    build_slides_array_from_files();

    $website_title = get_website_title();

    $return = "
        <h2 style='text-align: center;'>Configure Carousel screen</h2>
        <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>";

// first create a website_title update block

    $return .= "
        <div class='container'>
            <div class='row justify-content-center mt-3 mb-3 pt-2 pb-2' style='background: white;'>
                <form>
                    <label for = 'websitetitle'>&nbsp;&nbsp;Website title :&nbsp;</label>
                    <input type='text' maxlength='60' size='25' id = 'websitetitle' 
                        autocomplete='off' value = '$website_title'
                        title='Enter a title for the website - eg \"Newbiggin Parish Council\"'
                        onmousedown='clearMessageArea();'>
                    <button id = 'updatebutton'  type='button' class='ml-2 btn-sm btn-primary'
                        title='Update the website title'
                        onclick='updateWebsiteTitle();'>Update
                    </button>
                </form>
            </div>
        </div>";

// now create a slide insert block

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

    $return .= "
        <div class='container'>
            <div id='slidessortablelist' class='list-group'>";

    for ($i = 0; $i < count($slides); $i++) {

        $slide_title = $slides[$i]['slide_title'];

        $return .= "
                <div class='row justify-content-center pt-2 pb-2' style='background: white;'>
                    <div class='list-group-item mb-0 pb-0'>
                        <form enctype='multipart/form-data' method='post' name='slideform$i'>
                            <span id = 'slide$i' class = 'slideentry' style = 'display: none;'>$i</span> 
                            <label for ='slidetitle$i'>&nbsp;&nbsp;Title :&nbsp;</label> 
                            <input type='text' maxlength='60' size='15' id='slidetitle$i' 
                                autocomplete='off' value='$slide_title' 
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

#####################  update_website_title ####################

if ($helper_type == "update_website_title") {

    $website_title = $_POST['website_title'];

    save_website_title($website_title);
}

#####################  insert_slide ####################

if ($helper_type == "insert_slide") {

    $slide_title = $_POST['slide_title'];

// get the current $slides array

    build_slides_array_from_files();

// check there's not already a slide with this title

    for ($i = 0; $i < count($slides); $i++) {
        if ($slides[$i]['slide_title'] == $slide_title) {
            echo "Oops - a slide already exists for this title";
            exit(0);
        }
    }

// get the file name and type

    $slide_file_name = $_FILES["slidefilename"]["name"];
    $info = pathinfo($slide_file_name);
    $slide_file_extension = $info['extension'];

// add the row to $slides;

    $slides_precursor[] = ['slide_title' => $slide_title,
        'slide_file_extension' => $slide_file_extension];

// write the updated $slides arrray to slides_configuration.txt

    rewrite_slides_configuration_file();

// and upload the slide

    $upload_target = "../slides/$slide_title.$slide_file_extension";

    if (move_uploaded_file($_FILES['slidefilename'] ['tmp_name'], $upload_target)) {
        echo "Slide successfully uploaded";
    } else {
        echo "Oops - slide upload failed";
    }
}

#####################  update_slide ####################

if ($helper_type == "update_slide") {


    $slide_index = $_POST['slide_index'];
    $new_slide_title = $_POST['slide_title'];
    $source_status = $_POST['source_status'];

    // get the $slides array for the current representation of the slide

    build_slides_array_from_files();

    // get the current title of the slide_index'th entry

    $current_slide_title = $slides[$slide_index]['slide_title'];
    $current_slide_file_extension = $slides[$slide_index]['slide_file_extension'];
    $current_slide_filename = "$current_slide_title.$current_slide_file_extension";

    // We need to check that any new name for the current title doesn't clash with any existing
    // other title. Strictly we'd only bother if the file extension for such an existing entry 
    // matched the extension for the current title, but this just causes confusion. the code
    // below enforces the stricter restriction of unique title - it makes things a lot simpler!

    $file_is_unique = true;

    for ($i = 0; $i < count($slides); $i++) {

        if ($i != $slide_index) {
            if ($slides[$i]['slide_title'] == $new_slide_title) {
                $file_is_unique = false;
            }
        }
    }

    $new_title = false;
    if ($new_slide_title != $current_slide_title)
        $new_title = true;
    $new_file = false;
    if ($source_status == "new source file provided")
        $new_file = true;

    if ($new_file) {

        if ($new_title) {

            $new_file_extension = pathinfo($_FILES['slidefilename' . $slide_index]["name"])['extension'];
            $target = "$new_slide_title.$new_file_extension";
            $upload_target = "../slides/$target";

            // upload to new target : new target must be unique

            if ($file_is_unique) {

                $slides_precursor[$slide_index]['slide_title'] = $new_slide_title;

                if (move_uploaded_file($_FILES['slidefilename' . $slide_index] ['tmp_name'], $upload_target)) {
                    echo "upload succeeded";
                } else {
                    echo "upload %failed% - move_uploaded in update_slide";
                    exit(0);
                }
            } else {
                echo"Oops - a slide already exists for this title";
                exit(0);
            }
        } else {

            // upload to old target - don't need to check for uniqueness           

            $new_file_extension = pathinfo($_FILES['slidefilename' . $slide_index]["name"])['extension'];
            $target = "$current_slide_title.$new_file_extension";
            $upload_target = "../slides/$target";

            if (move_uploaded_file($_FILES['slidefilename' . $slide_index] ['tmp_name'], $upload_target)) {
                echo "upload succeeded";
            } else {
                echo "upload %failed% - move_uploaded in update_slide";
                exit(0);
            }
        }
    } else {

        if ($new_title) {

            // rename old target - need to check new name is unique

            if ($file_is_unique) {

                $slides_precursor[$slide_index]['slide_title'] = $new_slide_title;

                $old_target = "$current_slide_title.$current_slide_file_extension";
                $new_target = "$new_slide_title.$current_slide_file_extension";
                $rename_old_target = "../slides/$old_target";
                $rename_new_target = "../slides/$new_target";

                if (rename($rename_old_target, $rename_new_target)) {
                    echo "rename succeeded";
                } else {
                    echo "rename %failed% - rename in update_slide";
                    exit(0);
                }
            } else {
                echo "Oops - a slide already exists for this title";
                exit(0);
            }
        } else {

            // nothing to do 
            echo "nothing to do";
        }
    }

    // rewrite the slides_configuration file 

    if (rewrite_slides_configuration_file()) {
        echo "slide update succeeded";
    } else {
        echo "rewrite_slides_configuration_file %%failed%% in update_slide";
    }
}

#####################  delete_slide ####################

if ($helper_type == "delete_slide") {

    $slide_index = $_POST['slide_index'];

// get the current $slides definitions

    build_slides_array_from_files();

// now get the name of the slide for the $slides_index'th entry

    $slide_title = $slides[$slide_index]['slide_title'];
    $slide_file_extension = $slides[$slide_index]['slide_file_extension'];
    $slide_filename = "$slide_title.$slide_file_extension";

// remove the $slides_index'th entry from $slides_precursor

    unset($slides_precursor[$slide_index]);

// delete the slide file itelf

    if (!unlink('../slides/' . $slide_filename)) {
        echo "Oops! unlink %%failed%% in delete_slide.";
        exit(1);
    }

    // Pack the precursor array (see notes on delete_section.

    $temp = array();
    $j = 0;

    foreach ($slides_precursor as $key => $value) {
        $temp[$j] = $slides_precursor[$key];
        $j++;
    }

    $slides_precursor = $temp;

// rewrite the updated slides array to slides_confguration.txt

    if (rewrite_slides_configuration_file()) {
        echo "Deletion succeeded";
    } else {
        echo "Oops - deletion failed";
    }
}

#####################  reorder_slides ####################

if ($helper_type == "reorder_slides") {

    $slides_precursor_json = $_POST['slides_precursor_json'];

// convert this to a slides_precursor array

    $slides_precursor = json_decode($slides_precursor_json, true);

// use this to overwrite the slides_configuration file

    if (rewrite_slides_configuration_file()) {
        echo "Reorder succeeded";
    } else {
        echo "Oops - reorder failed";
    }
}

#####################  build_entries_update_table ####################

if ($helper_type == "build_entries_update_table") {

    $section_id = $_POST['section_id'];

// get the current $sections definitions

    build_sections_array_from_files();

// get the index of the entry for $section_id. As with build_picklist, default to first entry
// if supplied section_id cannot be located

    $section_index = 0;

    for ($i = 0; $i < count($sections); $i++) {

        if ($sections[$i]['section_id'] == $section_id) {
            $section_index = $i;
            break;
        }
    }

    $section_id = $sections[$section_index]['section_id'];
    $section_type = $sections[$section_index]['section_type'];
    $section_header = $sections[$section_index]['section_header'];

    if ($section_type == "date_title") {
        $section_prefix = $sections[$section_index]['section_prefix'];
    } else {
        $section_prefix = "Entry for : ";
    }

    $today = date("Y-m-d");

// include a hidden span in the heading to return section_type to manager.html

    $return = "
        <h2 style='text-align: center;'>Update screen for \"$section_id\" Entries</h2>
            <span id='currentsectiontype' style='display: none;'>$section_type</span>
            <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>";

// Build an "insert" row for the section

    $return .= "
        <div class='container'>
            <div class='row justify-content-center mt-3 mb-3 pt-2 pb-2' style='background: white;'>
                <form enctype='multipart/form-data' method='post' name='entryform'>
                    <label for ='entrydate'>&nbsp;&nbsp;$section_prefix</label>";

    if ($section_type == "date_title") {
        $return .= "
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

    $return .= "
        <div class='container'>";

    for ($i = 0; $i < count($sections[$section_index]['entries']); $i++) {

// For a "date_title" entry, the value in 'entries' will be its date.
// For a 'standard_title' entry, the value in 'entries' will be its title. 
// In eiher case, the filename of the entry will be section_id_entry.pdf

        $entry_filename = $section_id . "_";

        if ($section_type == "date_title") {
            $entry_date = $sections[$section_index]['entries'][$i]['entry_date'];
            $entry_suffix = $sections[$section_index]['entries'][$i]['entry_suffix'];
            $entry_filename .= $entry_date . "_" . $entry_suffix;
        } else {
            $entry_title = $sections[$section_index]['entries'][$i]['entry_title'];
            $entry_filename .= $entry_title;
        }

        $entry_filename .= ".pdf";

// tuck the entry_filename away in a hidden span as currententryfilename$i

        $return .= "
            <div class='row justify-content-center pt-2 pb-2' style='background: white;'>
                <form enctype='multipart/form-data' method='post' name='entryform$i'>
                    <span id = 'currententryfilename$i' style = 'display: none;'>$entry_filename</span>
                    <button type='button'
                        class='btn-sm btn-primary'
                        title='Preview the file currently linked to this entry' 
                        onmousedown='clearMessageArea(); openFile(\"$entry_filename\");'>Preview
                    </button>
                    <label for ='entrydate$i'>&nbsp;&nbsp;$section_prefix</label>";

        if ($section_type == "date_title") {
            $return .= "
                    <input type='text' maxlength='10' size='10' id='entrydate$i' 
                        autocomplete='off' value = '$entry_date'
                        title = 'Enter the date for this entry'
                        onmousedown='clearMessageArea(); applyDatepicker(\"entrydate$i\");'>
                    <label for ='entrysuffix$i'>&nbsp;&nbsp;Suffix : </label> 
                    <input type='text' maxlength='40' size='10' id='entrysuffix$i' 
                        value = '$entry_suffix'
                        autocomplete='off' 
                        title = 'Enter the suffix for this entry (if any)'
                        onmousedown='clearMessageArea();'>";
        } else {
            $return .= "
                    <input type='text' maxlength='40' size='20' id='entrytitle$i' 
                        autocomplete='off' value = '$entry_title'
                        title = 'Enter the title for this entry'
                        onmousedown='clearMessageArea();'>";
        }

        $return .= "
                    <label for ='entryfilename$i'>&nbsp;&nbsp;Filename : </label> 
                    <input type='file' id='entryfilename$i' name='entryfilename$i'
                        accept='application/pdf'
                        title = 'Select a pdf file for this entry'
                        onmousedown='clearMessageArea();'>
                    <button type='button' id='updatebutton'
                        class='mr-2 btn-sm btn-primary'
                        title='Update this entry'
                        onclick='updateEntry($i);'>Update
                    </button>
                    <button type='button' id='deletebutton'
                        class='btn-sm btn-primary'
                        title='Delete this entry'
                        onclick='deleteEntry(\"$entry_filename\");'>Delete
                    </button>
                </form>
            </div>";
    }

    $return .= "
        </div>";

    echo $return;
}

#####################  insert_entry ####################

if ($helper_type == "insert_entry") {

    $section_id = $_POST['section_id'];
    $section_type = $_POST['section_type'];

    if ($section_type == "date_title") {
        $entry_date = $_POST['entry_date'];
        $entry_suffix = $_POST['entry_suffix'];
        $filename = $section_id . "_" . $entry_date . "_";
        if ($entry_suffix != '') {
            $filename .= $entry_suffix;
        }
    } else {
        $entry_title = $_POST['entry_title'];
        $filename = $section_id . "_" . $entry_title;
    }

    $filename .= ".pdf";

// check if file is unique

    $file_is_unique = true;
    if ($files = scandir('../entries')) {
        for ($i = 0; $i < count($files); $i++) {
            if ($files[$i] != "." && $files[$i] != "..") {
                if ($filename == $files[$i]) {
                    $file_is_unique = false;
                }
            }
        }
    } else {
        echo "insert %failed% - scandir in insert_entry";
        exit(0);
    }

    if ($file_is_unique) {

// OK - all good  - try to upload the file

        if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1')) {
            $upload_target = "D:\\Abyss Web Server\\htdocs\\parishcouncil\\entries\\$filename";
        } else {
            //          $upload_target = "/home/qfgavcxt/public_html/parishcouncil/entries/$filename";
            $upload_target = "../entries/$filename";
        }
        if (move_uploaded_file($_FILES['entryfilename'] ['tmp_name'], $upload_target)) {
            echo "insert succeeded";
        } else {
            echo "insert %failed% - move_uploaded in insert_entry";
        }
    } else {
        if ($section_type == "date_title") {
            echo"Oops - an entry already exists for this combination of date and suffix";
            exit(0);
        } else {
            echo "Oops - an entry already exists for this title";
            exit(0);
        }
    }
}

#####################  update_entry ####################

if ($helper_type == "update_entry") {

    $entry_index = $_POST['entry_index'];
    $section_id = $_POST['section_id'];
    $section_type = $_POST['section_type'];
    $current_filename = $_POST['current_filename'];

    if ($section_type == "date_title") {
        $entry_date = $_POST['entry_date'];
        $entry_suffix = $_POST['entry_suffix'];
        $new_filename = $section_id . "_" . $entry_date;
        if ($entry_suffix != '') {
            $new_filename .= "_" . $entry_suffix;
        }
    } else {
        $entry_title = $_POST['entry_title'];
        $new_filename = $section_id . "_" . $entry_title;
    }
    $source_status = $_POST['source_status'];

    $new_filename .= ".pdf";

// if $new_filename differs from $current_filename, see if  $new_filename is unique

    $file_is_unique = true;

    if ($new_filename != $current_filename) {
        if ($files = scandir('../entries')) {
            for ($i = 0; $i < count($files); $i++) {
                if ($files[$i] != "." && $files[$i] != "..") {
                    if ($new_filename == $files[$i]) {
                        $file_is_unique = false;
                    }
                }
            }
        } else {
            echo "update %failed% - scandir in update_entry";
            exit(0);
        }
    }

    if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1')) {
        $upload_target = "D:\\Abyss Web Server\\htdocs\\parishcouncil\\entries\\$new_filename";
    } else {
        $upload_target = "../entries/$new_filename";
    }

    if ($source_status == "new source file provided") {

// see if the target has changed 

        if ($new_filename != $current_filename) {

// upload to new target : new target must be unique

            if ($file_is_unique) {

                if (move_uploaded_file($_FILES['entryfilename' . $entry_index] ['tmp_name'], $upload_target)) {
                    echo "upload succeeded";
                } else {
                    echo "upload %failed% - move_uploaded in update_entry";
                }
            } else {
                if ($section_type == "date_title") {
                    echo"Oops - an entry already exists for this combination of date and suffix";
                    exit(0);
                } else {
                    echo "Oops - an entry already exists for this title";
                    exit(0);
                }
            }
        } else {

// upload to old target - don't need to check for uniqueness

            if (move_uploaded_file($_FILES['entryfilename' . $entry_index] ['tmp_name'], $upload_target)) {
                echo "upload succeeded";
            } else {
                echo "upload %failed% - move_uploaded in update_entry";
            }
        }
    } else {

        if ($new_filename != $current_filename) {

// rename old target - need to check new name is unique

            if ($file_is_unique) {

                if (rename("../entries/$current_filename", "../entries/$new_filename")) {
                    echo "rename succeeded";
                } else {
                    echo "rename %failed% - rename in update_entry";
                }
            } else {
                if ($section_type == "date_title") {
                    echo"Oops - an entry already exists for this combination of date and suffix";
                    exit(0);
                } else {
                    echo "Oops - an entry already exists for this title";
                    exit(0);
                }
            }
        } else {

// nothing to do 
            echo "nothing to do";
        }
    }
}

#####################  delete_entry ####################

if ($helper_type == "delete_entry") {

    $filename = $_POST['filename'];

    if (!unlink('../entries/' . $filename)) {
        echo "Oops! unlink %%failed%% in delete_entry.";
        exit(1);
    } else {
        echo "file successfully deleted";
    }
}
    