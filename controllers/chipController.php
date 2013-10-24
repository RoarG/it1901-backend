<?php
/*
 * File: chipController.php
 * Holds: The chipController-class with all the updates from a chip mounted on a sheep
 * Last updated: 24.10.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class ChipController extends REST {

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
    
    // Update a sheep with chip-data
    protected function put_chip ($id) {
        // Check if the sheep and/or hash exists
        if (isset($_GET['sheep_token'])) {
            $get_sheep = "SELECT sh.*, sh_sys.system
            FROM sheep sh 
            LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep = sh.id
            LEFT JOIN system AS sys ON sh_sys.system = sys.id
            WHERE sys.sheep_token = :sheep_token
            AND sh.id = :id
            ORDER BY sh.id ASC";
            
            $get_sheep_query = $this->db->prepare($get_sheep);
            $get_sheep_query->execute(array(':sheep_token' => $_GET['sheep_token'], ':id' => $id));
            $row = $get_sheep_query->fetch(PDO::FETCH_ASSOC);
            
            // Checking if sheep exists
            if (!isset($row['id'])) {
                $this->setReponseState(141, 'No such sheep');
            }
            else {
                // Check what kind of update we are talking about
                if (!isset($_POST['type'])) {
                    // Missing update-type
                    $this->setReponseState(178, 'Missing update-type');
                }
                else {
                    // Check if valid update-type
                    if (in_array($_POST['type'], array('position', 'wounded', 'killed'))) {
                        // Handle each type of update-type
                        if ($_POST['type'] == 'position') {
                            // Update position only
                            $post_sheep = "UPDATE sheep
                            SET identification = :identification,
                            lat = :lat,
                            lng = :lng,
                            WHERE id = :id";
                            
                            $post_sheep_query = $this->db->prepare($post_sheep);
                            $post_sheep_query->execute(array(':lat' => $_POST['lat'], ':lng' => $_POST['lng'], ':id' => $id));
                        }
                        else if ($_POST['type'] == 'wounded') {
                            // Dummy update to force "last updated" to match
                            $post_sheep = "UPDATE sheep
                            SET alive = 1
                            WHERE id = :id";
                            
                            $post_sheep_query = $this->db->prepare($post_sheep);
                            $post_sheep_query->execute(array(':id' => $id));
                            
                            // Notification!
                            $send_notification = "INSERT INTO notification
                            (system, text, sheep)
                            VALUES (:system, :text, :sheep)";
                    
                            $send_notification_query = $this->db->prepare($send_notification);
                            $send_notification_query->execute(array(':system' => $row['system'], ':text' => $row['name']. '(#'.$row['identification'].') er skadet!', ':sheep' => $id));
                        }
                        else {
                            // Sheep died!!!
                            $post_sheep = "UPDATE sheep
                            SET alive = 0
                            WHERE id = :id";
                            
                            $post_sheep_query = $this->db->prepare($post_sheep);
                            $post_sheep_query->execute(array(':id' => $id));
                            
                            // Notification!
                            $send_notification = "INSERT INTO notification
                            (system, text, sheep)
                            VALUES (:system, :text, :sheep)";
                    
                            $send_notification_query = $this->db->prepare($send_notification);
                            $send_notification_query->execute(array(':system' => $row['system'], ':text' => $row['name']. '(#'.$row['identification'].') ble nettopp drept!', ':sheep' => $id));
                        }
                    }
                    else {
                        // Invalid update-type
                        $this->setReponseState(179, 'Wrong update-type');
                    }
                }
            }
        }
        else {
            // Missing sheep_token
            $this->setReponseState(177, 'Missing sheep_token');
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