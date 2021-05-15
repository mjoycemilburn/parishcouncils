<?php

session_start();

# Remember that session_destroy() does not unset $_SESSION at the moment it is
# executed. $_SESSION is unset when the current script has stopped running.

if (session_destroy()) {
        echo 'The Session has been destroyed';

} else {
    echo "Destroy failed : Loc 1";
}
