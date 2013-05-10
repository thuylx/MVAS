<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Hooker extends MX_Controller
{    
    public function pre_execution()
    {        
        //Do auto correction as module level
        if ($this->ORI_MO->keyword != 'UDF') //Do not correct UDF MO by rules
        {
            $this->ORI_MO->auto_correct($this->MO_model->get_auto_correct_rules('lottery'));            
        }                
        
        if ($this->ORI_MO->is_changed('keyword'))
        {
            if ($this->scp->re_direct($this->ORI_MO->keyword) === FALSE)
            {
                $this->ORI_MO->status = "error_mo_incorrect";
                $this->ORI_MO->keyword = "error_mo_incorrect";                  
            }
        }                
    }
}
/* End of File */