<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Xvip extends MY_Controller
{
    private $new_password = '';
    
    public function __construct()
    {
        parent::__construct();        
    }
     
    public function preprocess_THU_DO()
    {
    }       
    
    public function load_resp_code($string)
    {
        $err = explode('|',$string);
        if ($err) return $err[0];
        return NULL;        
    }

    public function load_resp_msg($string)
    {
        var_dump($string);
        $err = explode('|',$string);
        if ($err) return $err[1];
        return NULL;
    }    
}

/* End of file*/