<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Xvip extends MY_Controller
{
    private $new_password = '';
    
    public function __construct()
    {
        parent::__construct();        
    }
     
    public function preprocess_XVIP()
    {
        $args = explode(' ',$this->MO->argument); 
        $this->MO->args['change_password'] = (strtoupper($args[0]) == 'MK');
    }
    
    public function load_new_password()
    {
        if ($this->new_password == '')
        {
            $this->load->helper('string');
            $this->new_password = random_string();            
        }
        return $this->new_password;
    }  
    
    public function load_err_code($string)
    {
        $err = explode('|',$string);
        if ($err) return $err[0];
        return NULL;
    }
    
    public function load_err_msg($string)
    {
        $err = explode('|',$string);
        if ($err) return $err[1];
        return NULL;
    }    
}

/* End of file*/