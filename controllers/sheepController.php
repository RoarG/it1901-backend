<?php
/*
 * File: sheepController.php
 * Holds: The sheepController-class with all the methods for the sheep-calls
 * Last updated: 16.09.13
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

    public function __construct($response) {
        // Loading the class-name, setting it in the REST-class, so we can check if it holds the method being called
        $this->className = get_class($this);

        // Calling RESTs constructor
        parent::__construct($response);
    }

    //
    // Api-methods
    //
    
    // Getting a list with all the sheeps for the current system
    protected function get_sheep() {
        // Defining return-array
        $ret = array();
        $ret['sheep'] = array();
        
        // Getting all sheeps for the current system
        $get_all_sheeps = "SELECT sh.id, sh.identification, sh.name, sh.alive
        FROM sheep sh 
        LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep = sh.id
        WHERE sh_sys.system = :system
        ORDER BY sh.id ASC";
        
        $get_all_sheeps_query = $this->db->prepare($get_all_sheeps);
        $get_all_sheeps_query->execute(array(':system' => $this->system));
        while ($row = $get_all_sheeps_query->fetch(PDO::FETCH_ASSOC)) {
            // Adding the row to the array
            $ret['sheep'][] = $row;
        }
        
        return $ret;
    }
    
    // Get all information about one sheep
    protected function get_sheep_single($id) {
        // Defining return-array
        $ret = array();
        
        // Get information about one sheep (if it exists)
        $get_sheep = "SELECT sh.*
        FROM sheep sh 
        LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep = sh.id
        WHERE sh_sys.system = :system
        AND sh_sys.sheep = :id
        ORDER BY sh.id ASC";
        
        $get_sheep_query = $this->db->prepare($get_sheep);
        $get_sheep_query->execute(array(':system' => $this->system, ':id' => $id));
        $row = $get_sheep_query->fetch(PDO::FETCH_ASSOC);
        
        // Checking if sheep exists
        if (!isset($row['id'])) {
            $this->setReponseState(141, 'No such sheep');
        }
        
        return $row;
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