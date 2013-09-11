<?php
/*
 * File: sheepController.php
 * Holds: The sheepController-class with all the methods for the sheep-calls
 * Last updated: 11.09.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class SheepController extends REST {

    //
    // The constructor for this subclass
    //

    public function __construct() {
        // Loading the class-name, setting it in the REST-class, so we can check if it holds the method being called
        $this->className = get_class($this);

        // Calling RESTs constructor
        parent::__construct();
    }

    //
    // Api-methods
    //
    
    // Getting a list with all the sheeps for the current system
    protected function get_sheep() {
        
    }
}

//
// Loading the class-name dynamically and creating an instance doing our magic
//

// Getting the current file-path
$path = explode('/',__FILE__);

// Including the run-script to execute it all
include_once "run.php";
?>