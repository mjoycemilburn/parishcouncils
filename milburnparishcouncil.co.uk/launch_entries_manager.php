<?php

# mini launchpad to allow you to set a version number for the manager

# create header to pre-expire the page and make sure the browser reloads it.This ensures that
# php and html references also get reloaded if their version numbers have been changed
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$system_version_number = 23;

header("Location: https://milburnparishcouncil.co.uk/manager.php?ver=$system_version_number");
