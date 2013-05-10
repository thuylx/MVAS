<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Hooker extends MX_Controller
{
    public function pre_execution()
    {        
        //Do auto correction as module level
        //if ($this->ORI_MO->keyword != 'UDF') //Do not correct UDF MO by rules
        //{
        //    $this->ORI_MO->auto_correct($this->MO_model->get_auto_correct_rules('lottery'));            
        //}                                 
                
        /*
        //Redirect all of message to 7227 to service of LUCKY and a special case for Viettel
        if (($this->ORI_MO->short_code == '7227') || ($this->ORI_MO->short_code == '7527' && $this->ORI_MO->smsc_id == 'Viettel'))
        {
            //For XSDP (other than MB)
            if (strpos($this->ORI_MO->keyword,'DP'))
            {                                
                $this->ORI_MO->keyword = 'LUCKYDP';
                write_log('debug','Changed keyword to '.$this->ORI_MO->keyword);
            }
            //For KQ on 7227 (do this to match with which we printed on teicke)
            //For XSMB
            /*
            elseif ($this->ORI_MO->keyword == 'KQ')
            {
                 $this->ORI_MO->keyword = 'KQ7227';
            }
             * 
             */
        /*
            elseif ($this->ORI_MO->keyword != 'XSMN' && $this->ORI_MO->keyword != 'XSMT')
            {
                $this->ORI_MO->keyword = 'LUCKY';
                write_log('debug','Changed keyword to '.$this->ORI_MO->keyword);
            }            
        }                       
        
        if ($this->ORI_MO->is_changed('keyword'))
        {
            if ($this->scp->re_direct($this->ORI_MO->keyword) === FALSE)
            {
                $this->ORI_MO->status = "error_mo_incorrect";
                $this->ORI_MO->keyword = "error_mo_incorrect";                  
            }
        }                
        
        if ($this->ORI_MO->keyword == 'KQ')
        {
            $this->scp->controller = 'xs';
        }
        
        //Redirect Viettel to a special scenario
        if ($this->ORI_MO->smsc_id == 'Viettel')
        {            
            $this->scp->controller = $this->scp->controller.'_viettel';
        }            
         * 
         */    
                
                
        //-----------------------------------------------------------------------------------------------------
        // SET MESSAGE SIGNATURE
        //-----------------------------------------------------------------------------------------------------
        // Use these values to sign MT messsage before sending out. These values will be overwrite to default ones
        /*
        Default value is set in config/core.php:        
        $config['sms_signagures'] = array(
                                        "Cam on ban da su dung dich vu!",
                                        "Cam on ban da su dung dich vu",
                                        "Cam on ban!",
                                        "Cam on ban"
                                    );                
        
        $short_code = (isset($this->ORI_MO))?$this->ORI_MO->short_code:'7x27';
        $signatures = array(
            "\n$short_code - TONG DAI MAY MAN CUA BAN!",
            "\n$short_code - TONG DAI MAY MAN CUA BAN",
            "\n$short_code TONG DAI MAY MAN CUA BAN",
            "\nTONG DAI MAY MAN $short_code",
            "\nTONG DAI MAY MAN",
            "\nCam on ban!",
            "\nCam on ban",
            "Cam on ban"            
        );
        $CI =& get_instance();
        $CI->config->set_item('sms_signagures',$signatures);
         * 
         */
    }
}
/* End of File */