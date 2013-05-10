<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//class MGW extends MX_Controller
class Soap_mgw
{
    /**
     *Send SMS
     * @param string $username
     * @param string $password
     * @param int $mo_id
     * @param string $short_code
     * @param string $msisdn
     * @param string $content
     * @param int $type
     * @param string $link
     * @return string 
     */
    public function send_sms($username, $password, $mo_id,$short_code,$msisdn,$content,$type=1,$link=NULL)
    {                                      
        //Load libraries
        $this->load->library('Mo',NULL,'MO');
        $this->load->library('Mt',NULL,'MT');
        $this->load->library('Mt_box',NULL,'MT_Box');
        $this->load->library('Mo_box',NULL,'MO_Box');        
        $this->load->library('Event_handler',NULL,'Event');
        $this->load->library('Environment_params',NULL,'Evr'); //For environment parameters
        $this->load->library('Provisioning');
        $this->load->library('Parser');               
        $this->load->library('Simplelogin'); 
        
        $this->MT_Box->set_max_cache_size($this->config->item('max_mt_box_cache_size'));
        $this->MO_Box->set_max_cache_size($this->config->item('max_mo_box_cache_size'));
        $this->provisioning->set_real_time_statistic($this->config->item('real_time_statistic'));        

        //Load models
        $this->load->model('Mo_model','MO_model');
        $this->load->model('Mt_model','MT_model');
        $this->load->model('Service_model');
        $this->load->model('Customer_model');    
        $this->load->model('Statistic_model');
        $this->load->model('Mo_queue_model','MO_Queue');                                  
        
        //$this->MO_Queue->set_distinct_mode($this->config->item('mo_queue_distinct_mode'));
        //$this->MO_Queue->set_distinct($this->config->item('queue_distinct_fields'));
        //$this->MO_Queue->set_queue_size($this->config->item('mo_queue_cache_size'));
                
        //Load helpers
        $this->load->helper('database');
        $this->load->helper('text');  
        
        
        //----------------------------------------------------------------------
        // Check message parameters
        //----------------------------------------------------------------------        
        $return ='';
        
        //mo_id        
        if ( ! isset($mo_id) || $mo_id == '')
        {
            $return = '1:mo_id is empty';
            write_log('error',"The mo_id field is empty",'mo');            
        }
        else
        {
            $mo = $this->MO_model->get_mo($mo_id);
            if ( ! $mo)
            {                
                write_log('error','The mo_id field is invalid','mo');
                $return = '2:mo_id is invalid';
            }
        }
        
        //short_code
        if ( ! isset($short_code) || $short_code == '')
        {            
            write_log('error',"The short_code field is empty",'mo');      
            $return = '3:short_code is empty';      
        }
        else
        {            
            if ( ! preg_match('/7[0-7]27/',$short_code))
            {                
                write_log('error','The short_code field is invalid','mo');
                $return = '4:short_code is invalid';
            }
        }
        
        //msisdn
        $pattern = $this->config->item('default','accepted_msisdn_pattern');
        if ( ! isset($msisdn) || ! preg_match("/$pattern/",$msisdn))
        {            
            write_log('error','MSISDN is invalid','mo');
            $return = '5:msisdn is invalid';
        }

        //content
        if ( ! isset($content) || $content == '')
        {            
            write_log('error','Content of message is empty','mo');
            $return = '6:msisdn is empty';
        }
                
        //type
        if ( ! isset($type) || $type == '')
        {
            //$return = '0:Warning - Message type is not defined, set to 1 as a text message.';
            write_log('error','Warning - Message type is not defined, set to 1 as a text message.','mo');
            $type = 1;
        }
        else
        {
            //link
            if ($type == 2 && ( ! isset($link) || $link == ''))
            {                
                write_log('error','Link is empty','mo');
                $return = '7:Link is empty for a wap-push';
            }            
        }
        
        //----------------------------------------------------------------------
        // Login first
        //----------------------------------------------------------------------                
        if ( ! isset($username) || ! isset($password))
        {
            write_log('error','Username and password are required!','mo');     
            $return = '8:Username and password are required!';       
        }
        else
        {
            $auth = $this->simplelogin->logged_in($username);
            if (! $auth)
            {
                $auth = $this->simplelogin->login($username,$password);
            }         
            
            if ( ! $auth)
            {
                write_log('error','Login failed.','mo');
                $return = '9:Login failed!';
            }                            
        }                                        
        
        if ( ! $return)
        {            
            $this->MT->mo_id = $mo_id;
            $this->MT->short_code = $short_code;
            $this->MT->msisdn = $msisdn;
            $this->MT->smsc_id = $this->MT_model->detect_smsc($msisdn,'telco');;
            $this->MT->content = $content;     
                   
            switch ($type)
            {            
                case 1: //Text SMS                    
                    $this->MT->no_signature = TRUE;
                    $this->MT->send();            
                    break;
                case 2: //WAP Push                               
                    $this->MT->udh = $link;                                
                    $this->MT->wap_push();            
                    break;
            }
            
            //Provision content
            $this->provisioning->process_mt_box();
            $return = 'sent';            
        }
        
        return $return;
    }    
}
/*End of file*/