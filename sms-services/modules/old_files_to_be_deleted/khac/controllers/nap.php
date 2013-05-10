<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class NAP extends MY_Controller
{    
    public function __construct()
    {
        parent::__construct();        
    }
    
    /*****************************************************
     * Pre process new coming MO before processing scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in coming MO content before processing
     *  processed argument should be stored in database.
     *****************************************************/      
    public function __parse_new_mo()
    {
        
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class.
     *****************************************************/    
    public function __initialize()
    {
        
    }   
    
    /*******************************************************
     * Load data for template
     *******************************************************/    
    protected function load_data($template)
    {
        switch ($template)
        {
            case 'template_01':             
                $data = $this->Lottery_model->get_statistic_min_max('MB',30,NULL,3);                                                             
            case 'template_02':
                $data['today'] = date("d/m");                
                return $data;        
                        
            case 'template_03':
                $data = $this->Lottery_model->get_statistic_min_max('MB',30,NULL,3);
                $data['balance'] = $this->get_total_balance($this->MO->msisdn,$this->MO->short_code) + $this->MO->balance;
                return $data;
        }
    }         
        
    /*****************************************************
     * Service scenario for short_code 7227     
     *****************************************************/     
    public function sc_7227()
    {
    }    
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    public function sc_default()
    {     
        try 
        { 
            $client = @new SoapClient("http://ws.msm.vn/getMOPartner?wsdl");             
        } catch (SoapFault $E){            
            write_log('error','Failed: '.$E->faultstring);
            return;                        
        }        
     
        try
        {                        
            $MT = array(
                'username'      => '7x27',
                'password'      => '7x27!@#321'
            );
            $result = $client->__soapCall('getMOFromPartNer',$MT);
            /*
            $msg = array(
                'username'      => 'test',
                'password'      => 'test',
                'mo_id'         => 2043185,
                'short_code'    => '7227',                
                'msisdn'        => '+84983098334',
                'content'       => 'Noi dung',                
                'type'          => 2,
                'link'          => 'http://media.nhacvietplus.com.vn:8007/WapPush/Music.aspx?code=viyeu&by=name&type=fulltrack&auth=f87ffe0efea1665eebf32ea60bd2c1e1'
            );
            
            //$result = $client->send_sms('test','test',2043185,'7227','+84983098334','Noi dung gui tin',2,'http://media.nhacvietplus.com.vn:8007/WapPush/Music.aspx?code=viyeu&by=name&type=fulltrack&auth=f87ffe0efea1665eebf32ea60bd2c1e1');
            $result = $client->__soapCall('send_sms',$msg);
            */
                                    
        } 
        catch (SoapFault $E)
        {                           
            write_log('error','Failed: '.$E->faultstring);
            return;     
        }                      

        if (! is_soap_fault($result)) {            
            write(highlight_info($result));    
            return;
        }
                
    }  
          
    //----------------------------------------------------
    // Event processing function
    // This function will be called once event_name raised automatically.
    // In this exapmle, lower case of event name is after_lottery_update    
    //----------------------------------------------------
    public function __event_after_udf()
    {
        
    } 
}

/* End of file*/