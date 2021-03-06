<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Responser extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();        
        $this->load->library('Expression');
        $this->load->model('Auto_response_model');
    }
    
    protected function __initialize()    
    {        
        //Init some more MO information        
        $this->Evr->mo->time = date_parse(date("Y-m-d H:i:s",$this->Evr->mo->time));
        
        //Init service keyword parameter
        $this->Evr->service = $this->service;
        
        //Init time        
        $this->Evr->time = getdate();
    }
      
        
    /**
     * AUTO REPLY
     * Auto send all of messages which generated by loaded templates.
     * User for auto reply services such as error handling (incorrect mo etc)
     *  with out database or other special information.
     **/        
    protected function sc_default()
    {                        
        $templates = $this->Auto_response_model->get_template_codes($this->service, $this->MO->short_code);
        
        if ($templates)
        {
            foreach ($templates as $template)
            {                
                $exp = $this->expression->evaluate($template->expression,$this->Evr);                           
                if ($exp)
                {
                    $this->MT->short_code = $this->parser->parse($template->from,$this->Evr);
                    $this->MT->msisdn = $this->parser->parse($template->to,$this->Evr);
                    $this->MT->smsc_id = $this->parser->parse($template->smsc,$this->Evr);                    
                    $msg = $this->generate_mt_message($template->template_code,$this->Evr);
                    $this->MT->load($msg);
                    $this->MT->no_signature = TRUE;
                    $this->MT->send();
                }                
            }            
        }
    }
    
    public function process_queue()
    {
        //Discard queue processing for this kind of service
        $this->sc_default();            
    }            
}

/* End of file*/