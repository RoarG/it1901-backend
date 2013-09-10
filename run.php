<?php
/*
 * File: run.php
 * Holds: Method to initiate the different controllers dynamically
 * Written by: Thomas Gautvedt
 * Last updated: 20.05.13
 * Project: GeoParty-REST
 * 
*/

// Splitting the file-name, removing the tile-extention
$name = explode('.',$path[count($path)-1]);

// Uppercasing the first letter to be nice and OOP-ish
$classToCall = ucfirst($name[0]);

// Creating a new instance
$controller = new $classToCall();

// Logging
$controller->doLog();

// Printing the final response
$controller->printResponse();
?>