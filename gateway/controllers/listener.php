<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Listener extends CI_Controller 
{
    
    public function __construct()
    {
        parent::__construct();        
        //For development;
        if (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing')
        {
            $this->output->enable_profiler(TRUE);
        }
    }    
    
    public function invoke()
    {                
        $uri = $this->uri->ruri_to_assoc(3);      
        $uri = $this->uri->assoc_to_uri($uri);               
        $base_dir = "/var/www/html/".ENVIRONMENT."/sms-services";
        
        if (ENVIRONMENT == 'production')
        {
            exec("/usr/bin/php $base_dir/cron.php --run=$uri --log-file=$base_dir/logs/listener-".date('Y-m-d').".htm > /dev/null 2>&1 &");
        }        
        elseif (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing')
        {
            exec("/usr/bin/php $base_dir/cron.php --evr=".ENVIRONMENT." --run=$uri --log-file=$base_dir/logs/listener-".date('Y-m-d').".htm --show-output",$out);
            foreach ($out as $line)
            {
                $this->output->append_output($line);
            }            
        }        
    }    
    
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */