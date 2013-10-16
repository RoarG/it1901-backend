<?php
/*
 * File: logController.php
 * Holds: The logController-class with all the methods for the log-calls
 * Last updated: 16.10.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class LogController extends REST {

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
    // Actually getting the log speficied by different page and limit-values
    //
    
    private function fetch_logs($page, $num) {
        // Array for containing unread notifications
        $unread = array();
        
        // Defining return-array
        $ret = array();
        $ret['log'] = array();
        
        // Calculating the offset
        $offset = ($page - 1) * $num;
        
        // Getting all sheeps with positions for the current system
        $get_logs = "SELECT *
        FROM log 
        WHERE system = :system
        ORDER BY sent DESC
        LIMIT ".$offset.", ".$num."";
        
        $get_logs_query = $this->db->prepare($get_logs);
        $get_logs_query->execute(array(':system' => $this->system));
        while ($row = $get_logs_query->fetch(PDO::FETCH_ASSOC)) {
            
            // Adding the row to the array
            $ret['log'][] = $row;
        }
        
        
        return $ret;
    }
    
    //
    // Api-methods
    //
    
    // Getting the first 20 notifications
    protected function get_log() {
        return $this->fetch_logs(1, 20);
    }
    
    // Getting 20 notifications with pagination
    protected function get_log_page($page) {
        return $this->fetch_logs($page, 20);
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