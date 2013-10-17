<?php
/*
 * File: userController.php
 * Holds: The UserController-class with all the methods for the user-calls
 * Last updated: 16.09.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class UserController extends REST {

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

    // Returning the current user
    protected function get_user() {
        $get_user = "SELECT id, email, name
        FROM user 
        WHERE id = :id";
        
        $get_user_query = $this->db->prepare($get_user);
        $get_user_query->execute(array(':id' => $this->id));
        $row = $get_user_query->fetch(PDO::FETCH_ASSOC);
        
        // Fetch systemname
        $get_system = "SELECT name
        FROM system 
        WHERE id = :system";
        
        $get_system_query = $this->db->prepare($get_system);
        $get_system_query->execute(array(':system' => $this->system));
        $temp_row = $get_system_query->fetch(PDO::FETCH_ASSOC);
        
        // Combine arrays
        $row['system'] = $temp_row['name'];
        
        // Check to see if the got a user or not
        if (isset($row['id']) and strlen($row['id']) > 0) {
            return $row;
        }
        else {
            $this->setReponseState(131, 'No such user');
            return false;
        }
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