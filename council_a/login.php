<?php

# create header to pre-expire the page and make sure the browser reloads it.
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// the arrangement below is essentially a version-control trick to ensure
// that any changes in the shared index.html file are transmitted to the
// satellite council systems

include "../council_shared_code/login.html";
