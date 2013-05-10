<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome_model extends CI_Model
{
	private $vars;
    
    public function __construct()
    {
        parent::__construct();
        write_log('debug','Constructing');
        $vars = array();              
    }

}
/* End of file*/