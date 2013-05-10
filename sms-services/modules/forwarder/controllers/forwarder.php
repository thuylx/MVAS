<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forwarder extends MY_Controller {        
    
    public function preprocess_FW()
    {
        if (preg_match('/^([\+0-9A-Za-z]+) *(.*)$/',$this->MO->argument,$matches))
        {                        
            $this->MO->args['to'] = $matches[1];
            $this->MO->args['content'] = (isset($matches[2]))?$matches[2]:NULL;
        }        
    }
            
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {
        if (preg_match('/^([\+0-9A-Za-z]+) *(.*)$/',$this->MO->argument,$matches))
        {            
            $phone = $matches[1];
            $content = (isset($matches[2]))?$matches[2]:NULL;
            if ($content)
            {
                //check phone book
                $phone = (isset($this->phone_book[$phone]))?($this->phone_book[$phone]):$phone;
                if ( ! is_array($phone))                
                {
                    $this->MT->msisdn = $phone;
                    $this->MT->content = $content;
                    $this->MT->no_signature = TRUE;
                    $this->MT->send();
                    return TRUE;
                }
                
                foreach($phone as $item)
                {
                    $this->MT->msisdn = $item;
                    $this->MT->content = $content;
                    $this->MT->no_signature = TRUE;
                    $this->MT->send();                    
                }
                return TRUE;
            }
        }          
    }      
}

/* End of file*/