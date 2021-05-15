<?php

// The arrangement below avoids two problems that arise when sharing php code:
//
// Firstly, when the code is referenced from XMLHttpRequests and the code is called from a url
// such as milburnparishcouncil.co.uk, relative references using the file relationship between
// the milburnparishcouncil filing and the council_shared_code filing don't work. For example,
// while reference like "../council_shared_code/php/index_helpers.php" will work fine in testing
// if the council_shared_code directory is at the same level as the milburnparishcouncil storage,
// these references will throw 404 (file not found) errors. Using absolute references such as
// https://applebyarchaeology.org.uk/ngatesystems.com/parishcouncils/council_shared_code/php/inde_helpers.php
// gets round the problem but this is messy - you have to set headers to permit cross-origin errors and
// during testing when the https file may not yet be available you may want to test for local operation
// and set a directory target for the XMLHttpRequests.
//
// Secondly, and more seriously since no solution has been found, this arrangement avoids a serious
// pitfall with regard to session-variable setting. Again, when the code is called from a url
// such as milburnparishcouncil.co.uk, while you can set a session reference in login_helpers.php
// but you won't see this in manager_helpers.php. The session-handling software seems to regard
// the two scripts as separate sessions. In this instance, using absolute references, as above, offers
// no solution.
//
// In any event, the arrangement below works well

require('../../council_shared_code/php/manager_helpers.php');