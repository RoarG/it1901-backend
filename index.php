<?php
/*
 * File: index.php
 * Holds: The Loader-class that loads the correct class based on the method being called, setting output and including all the stuff we need
 * Last updated: 10.09.13
 * Project: Prosjekt1
 * 
*/

//
// Debug
//

error_reporting(E_ALL);
ini_set('display_errors', '1');

//
// Timezone GMT+0
//

date_default_timezone_set('Europe/London');

//
// Set headers
//

header('Content-Type: application/json; charset=utf-8');

//
// Include the rest-class, functions, libs etc
//

require_once 'rest.php';
require_once 'lib/rand/rand.php';
require_once 'lib/password_hash/password_hash.php';

//
// The Loader-class, loads the correct class extended from REST depending on the method being called
//

class Loader {

    //
    // Internal variables
    //

    private $response = array();

    //
    // Constructor
    //

    public function __construct() {
        // Checking wether the path is set or not
        if (!isset($_GET['q'])) {
            $this->setReponseState(115,'Unknown method');
        }
        else {
            // We have a path, find the base-path to include the correct script
            if (strpos($_GET['q'],'/') !== false) {
                $path_split = explode('/',$_GET['q']);
                $path = $path_split[0];
            }
            else {
                $path = str_replace('/','',$_GET['q']);
            }

            // If the final path something, test to see if it matches a controller
            if (strlen($path) == 0) {
                $this->setReponseState(115,'Unknown method');
            }
            else {
                // Constructing the path and filename, removing dots and slashes to prevent hacking
                $file = dirname(__FILE__).'/controllers/'.strtolower(str_replace(array('.','/'),'',$path)).'Controller.php';

                // Checking to see if the file exsists
                if (file_exists($file)) {
                    require_once $file;
                }
                else {
                    $this->setReponseState(115,'Unknown method');
                }
            }
        }

        // If we have an response already, it's an error, display it
        if (count($this->response) > 0) {
            echo json_encode($this->response);
        }
    }

    //
    // Setting the error-code and msg
    //

    private function setReponseState($c,$msg) {
        $this->response['code'] = $c;
        $this->response['msg'] = $msg;
    }
}

//
// Initiating the loader
//

$loader = new Loader();
?>