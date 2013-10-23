<?php
/*
 * File: mapController.php
 * Holds: The mapController-class with all the methods for the map related calls
 * Last updated: 17.10.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class MapController extends REST {

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
    
    // Return all sheeps without any highlighted
    protected function get_map() {
        return $this->get_map_highlight(null);
    }
    
    // Return all sheeps with or without any highlighted
    protected function get_map_highlight($highlight) {
        // Defining return-array
        $ret = array();
        $ret['sheep'] = array();
        
        // Getting all sheeps with positions for the current system
        $get_all_position = "SELECT sh.id, sh.identification, sh.lat, sh.lng, sh.alive, sh.name, sh.last_updated
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
}

//
// Loading the class-name dynamically and creating an instance doing our magic
//

// Getting the current file-path
$path = explode('/',__FILE__);

// Including the run-script to execute it all
include_once "run.php";
?>