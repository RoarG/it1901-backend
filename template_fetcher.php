<?php
/*
 * File: template_fetcher.php
 * Holds: The Loader-class that loads the correct class based on the method being called, setting output and including all the stuff we need
 * Last updated: 12.09.13
 * Project: Prosjekt1
 * 
*/

class TemplateFetcher {

    //
    // Internal variables
    //

    private $routes = array(
        //
        'login' => array(
            'base' => 'login_base.html',
            ),
        );

    //
    // Constructor
    //

    public function __construct() {
        // TODO
    }
    
    public function get($tpl) {
        return $tpl;
    }
}
?>
