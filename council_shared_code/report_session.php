<?php
session_start();

if (isset($_SESSION['test'])) {
    echo  "report session says set ";
} else {
    echo "report session says set ";
}

