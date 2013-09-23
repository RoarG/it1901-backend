<?php
/*
 * File: contactController.php
 * Holds: The contactController-class with all the methods for listing and editing contacts
 * Last updated: 24.09.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class ContactController extends REST {

    //
    // The constructor for this subclass
    //

    public function __construct($response) {
        // Loading the class-name, setting it in the REST-class, so we can check if it holds the method being called
        $this->className = get_class($this);

        // Calling RESTs constructor
        parent::__construct($response);
    }
    
    //
    // Api-methods
    //
        
    // Returning the contacts for the current system
    protected function get_contact() {
        // Get the contact
        $get_contact = "SELECT contact
        FROM system
        WHERE id = :system";
        $get_contact_query = $this->db->prepare($get_contact);
        $get_contact_query->execute(array(':system' => $this->system));
        $contact = $get_contact_query->fetch(PDO::FETCH_ASSOC);
                     
        // Returning the list of contacts (already an json-string)
        return array('contact' => json_decode($contact['contact']));
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