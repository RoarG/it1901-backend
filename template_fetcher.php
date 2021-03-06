<?php
/*
 * File: template_fetcher.php
 * Holds: The Loader-class that loads the correct class based on the method being called, setting output and including all the stuff we need
 * Last updated: 17.10.13
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
             
        // Home
        'home' => array(
            'base' => 'base.html'
        ),
        
        // Notifications
        'notifications' => array(
            'base' => 'base.html',
            'base_view' => 'base_view.html',
            'row' => 'row.html',
        ),
        
        // Sheep_all
        'sheep_all' => array(
            'base' => 'base.html',
            'row' => 'row.html',
        ),
        
        // Sheel_single
        'sheep_single' => array(
            'base' => 'base.html',
        ),
        
        // Sheep_single_edit
        'sheep_single_edit' => array(
            'base' => 'base.html',
        ),
        
        // Sheep_map
        'sheep_map' => array(
            'base' => 'base.html',
        ),
        
        // Sheep_add
        'sheep_add' => array(
            'base' => 'base.html',
        ),
        
        // Admin_edit
        'admin_edit' => array(
            'base' => 'base.html',
        ),
        
        // Admin_alert
        'admin_alert' => array(
            'base' => 'base.html',
            'row' => 'row.html',
        ),
        
        // Admin_log
        'admin_log' => array(
            'base' => 'base.html',
            'row' => 'row.html',
        ),
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
        // Holding the template(s) we want to get
        $tpls = array();
        
        // Variable for returning the content
        $ret = array();
        
        // Check if we are loading more than one template
        if (strpos($tpl,',') === false) {
            // Only one template
            $tpls[] = $tpl;
        }
        else {
            // Multiple templates
            $tpls = explode(',',$tpl);
        }
        
        // Loop all the templates
        foreach ($tpls as $template) {
            // Checking if route exists
            if (array_key_exists($template, $this->routes)) {
                // Fetching the right collection of templates
                $fetch = $this->routes[$template];
                
                // Looping all the templates for this route
                foreach ($fetch as $k => $v) {
                    // Storing the current file with full path and everything
                    $current_file = $this->base.$template.'/'.$v;
                    
                    // Checking if the template exists
                    if (file_exists($current_file)) {
                        // Getting content from file and put it in the returning array
                        $output = preg_replace('/[ \t]+/', ' ', preg_replace('/[\r\n]+/', '', file_get_contents($current_file))); // http://stackoverflow.com/a/6394462/921563
                        
                        // Return the output
                        $ret[$template][$k] = $output;
                    }
                }
            }
        }
        
        // Return content to the api
        return $ret;
    }
}
?>
