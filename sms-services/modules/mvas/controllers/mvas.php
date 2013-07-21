<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mvas extends MY_Controller 
{
    public function __construct() {
        parent::__construct();
    }
    
    public function load_data($param)            
    {
        switch ($param[0])
        {
            case 'date':
                return array($param[1]=>date($param[1]));
        }
    }
}

/* End of file*/