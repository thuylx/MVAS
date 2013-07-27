<?php            
class Gwadmin extends CI_Controller {

    //var $url;
    
    function __construct()
    {
        parent::__construct();        
        //$this->url = $this->config->item('base_url')."/index.php/";
    }
            
    function index()
    {        
    	$this->load->view('gwadmin.html');        
    }
}

/* End of file Gwadmin.php */
/* Location: ./sms-services/controllers/Gwadmin.php */
