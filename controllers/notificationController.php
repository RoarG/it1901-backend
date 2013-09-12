<?php
/*
 * File: notificationController.php
 * Holds: The notificationController-class with all the methods for displaying notifications
 * Last updated: 12.09.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class NotificationController extends REST {

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
        
    protected function notification () {
        return $this->notification_page(0,20);
    }
    
    protected function notification_page($page, $num) {
        // TODO
    }
    
    protected function notification_dropdown() {
        return $this->notification_page(0,7);
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