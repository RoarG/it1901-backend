<?php
/*
 * File: template_fetcher.php
 * Holds: The Loader-class that loads the correct class based on the method being called, setting output and including all the stuff we need
 * Last updated: 16.09.13
 * Project: Prosjekt1
 * 
*/

class TemplateFetcher {

    //
    // Internal variables
    //
    
    private $base;
    
    //
    // Routes used to fetch templates
    //

    private $routes = array(
        // Login
        'login' => array(
            'base' => 'base.html'
        ),
             
        // Todo
        'home' => array(
            'base' => 'base.html'
        )
    );

    //
    // Constructor
    //

    public function __construct() {
        // Setting the correct path
        $this->base = dirname(__FILE__).'/templates/';
    }
    
    //
    // Get
    //
    
    public function get($tpl) {
        // Variable for returning the content
        $ret = array();
        
        // Checking if route exists
        if (array_key_exists($tpl, $this->routes)) {
            // Fetching the right collection of templates
            $fetch = $this->routes['login'];
            
            // Looping all the templates for this route
            foreach ($fetch as $k => $v) {
                // Storing the current file with full path and everything
                $current_file = $this->base.$tpl.'/'.$v;
                
                // Checking if the template exists
                if (file_exists($current_file)) {
                    // Getting content from file and put it in the returning array
                    $ret[$k] = preg_replace('/[ \t]+/', ' ', preg_replace('/[\r\n]+/', '', file_get_contents($current_file))); // http://stackoverflow.com/a/6394462/921563
                }
            }
        }
        
        // Return content to the api
        return $ret;
    }
}
?>
