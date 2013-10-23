<?php
/*
 * File: run.php
 * Holds: Method to initiate the different controllers dynamically
 * Last updated: 16.09.13
 * Project: Prosjekt1
 * 
*/

// Splitting the file-name, removing the tile-extention
$name = explode('.',$path[count($path)-1]);

// Uppercasing the first letter to be nice and OOP-ish
$classToCall = ucfirst($name[0]);

// Creating a new instance
$controller = new $classToCall($this->getResponse());

// Printing the final response
$controller->printResponse();
?>