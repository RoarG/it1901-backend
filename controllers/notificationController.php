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
        
    protected function get_notification () {
        return $this->get_notification_page(0,20);
    }
    
    protected function get_notification_page($page, $num) {
        // Defining return-array
        $ret = array();
        $ret['sheep'] = array();
        
        // Getting all sheeps with positions for the current system
        $get_all_position = "SELECT sh.id, sh.identification, sh.lat, sh.lng, sh.alive
        FROM sheep sh 
        LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep = sh.id
        WHERE sh_sys.system = :system
        ORDER BY sh.id ASC";
        
        $get_all_position_query = $this->db->prepare($get_all_position);
        $get_all_position_query->execute(array(':system' => $this->system));
        while ($row = $get_all_position_query->fetch(PDO::FETCH_ASSOC)) {
            // Checking if highlighed
            if ($highlight != null and $highlight == $row['id']) {
                $row['highlight'] = 1;
                $ret['center'] = array('lat' => $row['lat'], 'lng' => $row['lng']);
            }
            
            // Adding the row to the array
            $ret['sheep'][] = $row;
        }
        
        // Generate center if there are no highlighted sheep
        if (!isset($ret['center'])) {
            $ret['center'] = $this->find_center($ret['sheep']);
        }
        
        return $ret;
    }
    
    protected function get_notification_dropdown() {
        return $this->get_notification_page(0,7);
    }
    
    protected function get_notification_num() {
        // Get number of unread notifications
        $get_notifications = "SELECT COUNT(id) as 'num_notifications'
        FROM notification
        WHERE system = :system
        AND is_read = 0";
        $get_notifications_query = $this->db->prepare($get_notifications);
        $get_notifications_query->execute(array(':system' => $this->system));
        $notifications = $get_notifications_query->fetch(PDO::FETCH_ASSOC);
        
        // Fix the number if the query returned null
        if ($notifications['num_notifications'] == null) {
            $notifications['num_notifications'] = 0;
        }
                    
        // Returning successful message to user with the new access_token
        return array('notifications' => $notifications['num_notifications']);
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