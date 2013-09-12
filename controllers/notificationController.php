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
    // Actually getting the notifications speficied by different page and limit-values
    //
    
    private function notifications($page, $num) {
        // Array for containing unread notifications
        $unread = array();
        
        // Defining return-array
        $ret = array();
        $ret['notifications'] = array();
        
        // Calculating the offset
        $offset = ($page - 1) * $num;
        
        // Getting all sheeps with positions for the current system
        $get_notifications = "SELECT *
        FROM notification 
        WHERE system = :system
        ORDER BY sent DESC
        LIMIT ".$offset.", ".$num."";
        
        $get_notifications_query = $this->db->prepare($get_notifications);
        $get_notifications_query->execute(array(':system' => $this->system));
        while ($row = $get_notifications_query->fetch(PDO::FETCH_ASSOC)) {
            if ($row['is_read'] == 0) {
                $unread[] = '('.$row['id'].',1)';
            }
            
            // Adding the row to the array
            $ret['notifications'][] = $row;
        }
        
        // Checking to see if we have unread notifications
        if (count($unread) > 0) {
            // Set the notifications to read
            $update_read = "INSERT INTO notification
            (id, is_read)
            VALUES ".implode(',',$unread)."
            ON DUPLICATE KEY UPDATE is_read=VALUES(is_read)";
            
            $update_read_query = $this->db->prepare($update_read);
            $update_read_query->execute();
        }
        
        return $ret;
    }
    
    //
    // Api-methods
    //
    
    // Getting the first 20 notifications
    protected function get_notification () {
        return $this->notifications(1,20);
    }
    
    // Getting 20 notifications with pagination
    protected function get_notification_page($page) {
        return $this->notifications($page,20);
    }
    
    // Getting the last 7 notifications (for the notification dropdownmenu)
    protected function get_notification_dropdown() {
        return $this->notifications(1,7);
    }
    
    // Returning the number of unread notifications
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