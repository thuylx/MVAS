<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Vnnplus extends MY_Controller
{            
    
    /*****************************************************
     * Pre process new coming MO before processing scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in coming MO content before processing
     *  processed argument should be stored in database.
     *****************************************************/      
    public function preprocess_DA()
    {
        $args = explode(' ',$this->MO->argument);
        $this->MO->args['code'] = $args[0];
        
        if (isset($args[1]))
        {
            preg_match('/(\+84|0)?([0-9]{9,10})/', $args[1], $matches);
            if ($matches)
            {
                $this->MO->args['destination'] = '+84'.$matches[2];
            }
        }        
    }        
        
    public function process_result()
    {
        $result = $this->Evr->Vnnplus_get->return;      
        if (strlen($result)>1) // = 1 mean return is error code
        {
            preg_match('|(.*):(http\:\/\/.*)|', $result,$matches);
            return array(
                'url' => $matches[2],
                'title' => $matches[1]
            );
        }
        else
        {
            return FALSE;
        }
    }           
}

/* End of file*/