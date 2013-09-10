<?php
/*
 * File: rand.php
 * Holds: Loads true random numbers from random.org to generate a "safe" access_token
 * Written by: Thomas Gautvedt
 * Last updated: 01.06.13
 * Project: GeoParty-REST
 * 
*/

class Random {
    
    //
    // Varables we're using in this class
    //
    
    private $rnd = array();
    
    //
    // The constructor
    //
    
    function __construct() {
        // Loading the content from random.org
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://www.random.org/integers/?num=10&min=1&max=10000&col=1&base=10&format=plain&rnd=new');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $data = curl_exec($ch);
        curl_close($ch);
        
        // Removing all empty lines
        $temp_rnd = explode("\n",$data);
        foreach ($temp_rnd as $v) {
            if (strlen($v) > 0) {
                // Adding the random number to the stack
                $this->rnd[] = $v;
            }
        }
    }
    
    //
    // Simple funtion that returns the random stack
    //
    
    public function getRnd() {
        return $this->rnd;
    }
}
?>