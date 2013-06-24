<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MT_Message
{
    /** @var string */
    public $mo_id;
    /** @var string */
    public $short_code;
    /** @var string */
    public $msisdn;
    /** @var string */
    public $content;
    /** @var int */
    public $type=1;
    /** @var string */
    public $link;
}


//class MGW extends MX_Controller
class MGW
{
    protected $CI;
    
    public function __construct()
    {
        $this->CI =& get_instance();
        //Load libraries
        $this->CI->load->library('Mo',NULL,'MO');
        $this->CI->load->library('Mt',NULL,'MT');
        $this->CI->load->library('Mt_box',NULL,'MT_Box');
        $this->CI->load->library('Mo_box',NULL,'MO_Box');        
        $this->CI->load->library('Event_handler',NULL,'Event');
        $this->CI->load->library('Environment_params',NULL,'Evr'); //For environment parameters
        $this->CI->load->library('Scp');
        $this->CI->load->library('Parser');               
        $this->CI->load->library('Simplelogin'); 
        
        $this->CI->MT_Box->set_max_cache_size($this->CI->config->item('max_mt_box_cache_size'));
        $this->CI->MO_Box->set_max_cache_size($this->CI->config->item('max_mo_box_cache_size'));
        $this->CI->scp->set_real_time_statistic($this->CI->config->item('real_time_statistic'));        

        //Load models
        $this->CI->load->model('Mo_model','MO_model');
        $this->CI->load->model('Mt_model','MT_model');
        $this->CI->load->model('Service_model');
        $this->CI->load->model('Customer_model');    
        $this->CI->load->model('Statistic_model');
        $this->CI->load->model('Mo_queue_model','MO_Queue');                                  

        //Load helpers
        $this->CI->load->helper('database');
        $this->CI->load->helper('text');  
                
    }
    
    private function do_send_sms($MT)
    {        
        if (is_array($MT)) $MT = (object)$MT;
        //----------------------------------------------------------------------
        // Check message parameters
        //----------------------------------------------------------------------        
        $return ='';
        
        //mo_id        
        if ( ! isset($MT->mo_id) || $MT->mo_id == '')
        {
            $return = '1:mo_id is empty';
            write_log('error',"The mo_id field is empty",'mo');            
        }
        else
        {
            $mo = $this->CI->MO_model->get_mo($MT->mo_id);
            if ( ! $mo)
            {                
                write_log('error','The mo_id field is invalid','mo');
                $return = '2:mo_id is invalid';
            }
        }
        
        //short_code
        if ( ! isset($MT->short_code) || $MT->short_code == '')
        {            
            write_log('error',"The short_code field is empty",'mo');      
            $return = '3:short_code is empty';      
        }
        else
        {            
            if ( ! preg_match('/7[0-7]27/',$MT->short_code))
            {                
                write_log('error','The short_code field is invalid','mo');
                $return = '4:short_code is invalid';
            }
        }
        
        //msisdn
        $pattern = $this->CI->config->item('default','accepted_msisdn_pattern');
        if ( ! isset($MT->msisdn) || ! preg_match("/$pattern/",$MT->msisdn))
        {            
            write_log('error','MSISDN is invalid','mo');
            $return = '5:msisdn is invalid';
        }

        //content
        if ( (! isset($MT->content)) || ($MT->content == ''))
        {            
            write_log('error','Content of message is empty','mo');
            $return = '6:content is empty'.$MT['content'];
        }
                
        //type
        if ( ! isset($MT->type) || $MT->type == '')
        {
            //$return = '0:Warning - Message type is not defined, set to 1 as a text message.';
            write_log('error','Warning - Message type is not defined, set to 1 as a text message.','mo');
            $MT->type = 1;
        }
        else
        {
            //link
            if ($MT->type == 2 && ( ! isset($MT->link) || $MT->link == ''))
            {                
                write_log('error','Link is empty','mo');
                $return = '7:Link is empty for a wap-push';
            }            
        }                                        
        
        if ( ! $return)
        {            
            $this->CI->MT->mo_id = $MT->mo_id;
            $this->CI->MT->short_code = $MT->short_code;
            $this->CI->MT->msisdn = $MT->msisdn;
            $this->CI->MT->smsc_id = $this->CI->MT_model->detect_smsc($MT->msisdn,'telco');;
            $this->CI->MT->content = $MT->content;     
                   
            switch ($MT->type)
            {            
                case 1: //Text SMS                    
                    $this->CI->MT->no_signature = TRUE;
                    $this->CI->MT->send();            
                    break;
                case 2: //WAP Push                               
                    $this->CI->MT->udh = $link;                                
                    $this->CI->MT->wap_push();            
                    break;
            }
            
            $return = 'sent';            
        }
        //$return = print_r($MT,TRUE);
        return $return;        
    }
    
    /**
     *Send SMS
     * @param string $username
     * @param string $password
     * @param MT_Message $MT
     * @return string 
     */
    public function send_sms($username, $password, $MT)
    {                  
        $return = '';
        //----------------------------------------------------------------------
        // Login first
        //----------------------------------------------------------------------                
        if ( ! isset($username) || ! isset($password))
        {
            write_log('error','Username and password are required!','mo');     
            $return = '101:Username and password are required!';       
        }
        else
        {
            $auth = $this->CI->simplelogin->logged_in($username);
            if (! $auth)
            {
                $auth = $this->CI->simplelogin->login($username,$password);
            }         
            
            if ( ! $auth)
            {
                write_log('error','Login failed.','mo');
                $return = '102:Login failed!';
            }                                  
        }
        
        if ($return) return $return;
        
        //----------------------------------------------------------------------
        // Do send
        //----------------------------------------------------------------------          
        
        $return = $this->do_send_sms($MT);
        
        $this->CI->scp->process_mt_box();
        
        return $return;
    }   
    
    /**
     *Send Bulk SMS
     * @param string $username
     * @param string $password
     * @param array $MT_array 
     * @return  string
     */
    public function send_bulk_sms($username, $password, $MT_array)
    {
        $return = '';
        //----------------------------------------------------------------------
        // Login first
        //----------------------------------------------------------------------                
        if ( ! isset($username) || ! isset($password))
        {
            write_log('error','Username and password are required!','mo');     
            $return = '101:Username and password are required!';       
        }
        else
        {
            $auth = $this->CI->simplelogin->logged_in($username);
            if (! $auth)
            {
                $auth = $this->CI->simplelogin->login($username,$password);
            }         
            
            if ( ! $auth)
            {
                write_log('error','Login failed.','mo');
                $return = '102:Login failed!';
            }                                  
        }
        
        if ($return) return $return;
        
        //----------------------------------------------------------------------
        // Do send
        //----------------------------------------------------------------------          
        $return = array();
        foreach ($MT_array as $MT)
        {
            $return[] = $this->do_send_sms($MT);
        }
        $return = implode(',', $return);
        
        $this->CI->scp->process_mt_box();
        
        return $return;                        
    }
        
    /**
     *Broadcast SMS to list of receiver
     * @param string $username
     * @param string $password
     * @param MT_Message $MT
     * @param array $receive_array 
     */
    public function broadcast_sms($username, $password, $MT, $receive_array)
    {
        return TRUE;
    }
}
/*End of file*/