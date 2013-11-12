<?php
/*
 * File: sheepController.php
 * Holds: The sheepController-class with all the methods for the sheep-calls
 * Last updated: 29.09.13
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
    // Calculate center from multiple lat,lng-coordinates
    //
    
    private function find_center($arr) { // http://stackoverflow.com/a/14231286/921563
        // Define all variables
        $x = 0;
        $y = 0;
        $z = 0;
        
        foreach ($arr as $v) {
            // To degrees
            $lat = $v['lat'] * (M_PI / 180);
            $lng = $v['lng'] * (M_PI / 180);
            
            // Adding the numbers
            $x += cos($lat) * cos($lng);
            $y += cos($lat) * sin($lng);
            $z += sin($lat);
        }
        
        // Finding average
        $x = $x/count($arr);
        $y = $y/count($arr);
        $z = $z/count($arr);
        
        // Calculating back to coordinates
        $lo = atan2($y, $x);
        $hy = sqrt($x * $x + $y * $y);
        $la = atan2($z, $hy);
        
        // Returning everything
        return array('lat' => (string)($la * (180 / M_PI)), 
                     'lng' => (string)($lo * (180 / M_PI)));
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
    
    // Create new sheep
    protected function post_sheep() {
        // Check if all the params we need is sat
        if ($this->checkRequiredParams(array('identification','name','birthday','weight','vaccine','chip'),$_POST)) {
            // Validate the content of the fields
            $error = false;
            foreach (array('identification','weight','vaccine','chip') as $k) {
                if (!is_numeric($_POST[$k])) {
                    $error = true;
                }
            }
            
            // Check if params were numeric or not
            if ($error) {
                // Maleformed input
                $this->setReponseState(182, 'Maleformed sheep-input');
            }
            else {
                // Validate date
                $date_split = explode('-',$_POST['birthday']);
                if (strlen($date_split[0]) != 4)
                    $error = true;
                if (strlen($date_split[1]) != 2)
                    $error = true;
                if (strlen($date_split[2]) != 2)
                    $error = true;
                
                // Check if date was validated correctly
                if (!$error) {
                    // Variable for checking that chip is unique
                    $chip_unique = true;
                    
                    // Generate center of sheep-herd and set that as default-position for the new sheep
                    $sheep_arr = array();
                    $get_all_position = "SELECT sh.chip, sh.lat, sh.lng
                    FROM sheep sh 
                    LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep = sh.id
                    WHERE sh_sys.system = :system
                    ORDER BY sh.id ASC";
                    
                    $get_all_position_query = $this->db->prepare($get_all_position);
                    $get_all_position_query->execute(array(':system' => $this->system));
                    while ($row = $get_all_position_query->fetch(PDO::FETCH_ASSOC)) {
                        // Add sheep to array
                        $sheep_arr[] = $row;
                        
                        if ($row['chip'] == $_POST['chip']) {
                            $chip_unique = false;
                            break;
                        }
                    }
                    
                    // Check if unique chip
                    if ($chip_unique) {
                        // Generate center if there are no highlighted sheep
                        $new_pos = $this->find_center($sheep_arr);
                        
                        // Insert the sheep
                        $post_sheep = "INSERT INTO sheep
                        (identification, chip, name, birthday, weight, vaccine, lat, lng)
                        VALUES (:identification, :chip, :name, :birthday, :weight, :vaccine, :lat, :lng)";
                        
                        $post_sheep_query = $this->db->prepare($post_sheep);
                        $post_sheep_query->execute(array(':identification' => $_POST['identification'], ':chip' => $_POST['chip'], ':name' => $_POST['name'], ':birthday' => $_POST['birthday'], ':weight' => $_POST['weight'], ':vaccine' => $_POST['vaccine'], ':lat' => $new_pos['lat'], ':lng' => $new_pos['lng']));
                        
                        // Get the sheep-id
                        $new_sheep_id = $this->db->lastInsertId();
                        
                        // Insert the system_sheep
                        $post_sheep2 = "INSERT INTO system_sheep
                        (system, sheep)
                        VALUES (:system, :sheep)";
                        
                        $post_sheep_query2 = $this->db->prepare($post_sheep2);
                        $post_sheep_query2->execute(array(':system' => $this->system, ':sheep' => $new_sheep_id));
                        
                        // Logging cration
                        $this->log('User '.$this->user_name.' (#'.$this->id.') opprettet en ny sau; '.$_POST['name'].' (#'.$_POST['identification'].')');
                        
                        return array('id' => $new_sheep_id);
                    }
                    else {
                        // Chip already in use!
                        $this->setReponseState(183, 'Chip already in use');
                    }
                }
                else {
                    // Maleformed input
                    $this->setReponseState(182, 'Maleformed sheep-input');
                }
            }
        }
        else {
            // Missing required params
            $this->setReponseState(181, 'Incomplete sheep-creation');
        }
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
    
    // Delete sheep with the provided id
    protected function delete_sheep_single($id) {
        // Defining return-array
        $ret = array();
        
        $get_sheep = "SELECT sh.name, sh.identification
        FROM sheep sh 
        LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep = sh.id
        WHERE sh_sys.system = :system
        AND sh_sys.sheep = :id
        ORDER BY sh.id ASC";
        
        $get_sheep_query = $this->db->prepare($get_sheep);
        $get_sheep_query->execute(array(':system' => $this->system, ':id' => $id));
        $row = $get_sheep_query->fetch(PDO::FETCH_ASSOC);
        
        // Delete the sheep
        $delete_sheep = "DELETE sheep, system_sheep
        FROM sheep, system_sheep
        WHERE system_sheep.sheep = sheep.id
        AND system_sheep.system = :system
        AND system_sheep.sheep = :id";
        
        $delete_sheep_query = $this->db->prepare($delete_sheep);
        $delete_sheep_query->execute(array(':system' => $this->system, ':id' => $id));
        
        // Logging deleting TODO, GET NAME AND IDENTIFICATION
        $this->log($this->user_name.' (#'.$this->id.') slettet sau '.$row['name'].' (#'.$row['identification'].').');
        
        return true;
    }
    
    // Create new sheep
    protected function put_sheep_single($id) {
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
        else {
            // We have a sheep, check if required data is presented
            if ($this->checkRequiredParams(array('identification','name','birthday','weight','vaccine','chip'),$_POST)) {
                // Validate the content of the fields
                $error = false;
                foreach (array('identification','weight','vaccine','chip') as $k) {
                    if (!is_numeric($_POST[$k])) {
                        $error = true;
                    }
                }
                
                // Check if params were numeric or not
                if ($error) {
                    // Maleformed input
                    $this->setReponseState(182, 'Maleformed sheep-input');
                }
                else {
                    // Validate date
                    $date_split = explode('-',$_POST['birthday']);
                    if (strlen($date_split[0]) != 4)
                        $error = true;
                    if (strlen($date_split[1]) != 2)
                        $error = true;
                    if (strlen($date_split[2]) != 2)
                        $error = true;
                    
                    // Check if date was validated correctly
                    if (!$error) {
                        // Check if the chip is already in use on another sheep
                        $get_identical_chip = "SELECT sh.id
                        FROM sheep sh 
                        LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep != sh.id
                        WHERE sh_sys.system = :system
                        AND sh_sys.sheep = :id
                        AND sh.chip = :chip
                        ORDER BY sh.id ASC";
        
                        $get_identical_chip_query = $this->db->prepare($get_identical_chip);
                        $get_identical_chip_query->execute(array(':system' => $this->system, ':id' => $id, ':chip' => $_POST['chip']));
                        $row2 = $get_identical_chip_query->fetch(PDO::FETCH_ASSOC);
        
                        // Checking if sheep exists
                        if (isset($row2['id'])) {
                            // Chip already in use!
                            $this->setReponseState(183, 'Chip already in use');
                        }
                        else {
                            // Insert the sheep
                            $post_sheep = "UPDATE sheep
                            SET identification = :identification,
                            chip = :chip,
                            name = :name,
                            birthday = :birthday,
                            weight = :weight,
                            vaccine = :vaccine,
                            comment = :comment
                            WHERE id = :id";
                            
                            $post_sheep_query = $this->db->prepare($post_sheep);
                            $post_sheep_query->execute(array(':identification' => $_POST['identification'], ':chip' => $_POST['chip'], ':name' => $_POST['name'], ':birthday' => $_POST['birthday'], ':weight' => $_POST['weight'], ':vaccine' => $_POST['vaccine'], ':comment' => $_POST['comment'], ':id' => $id));
                            
                            // Logging cration
                            $this->log($this->user_name.' (#'.$this->id.') endret info på '.$_POST['name'].' (#'.$_POST['identification'].').');
                            
                            return array('id' => $id);
                        }
                    }
                    else {
                        // Maleformed input
                        $this->setReponseState(182, 'Maleformed sheep-input');
                    }
                }
            }
            else {
                // Missing required params
                $this->setReponseState(181, 'Incomplete sheep-creation');
            }
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