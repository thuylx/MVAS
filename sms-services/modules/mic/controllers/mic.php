<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MIC extends MY_Controller
{
    public function __construct()
    {        
        parent::__construct();
        $this->load->model('Coi_model');        
    }
    
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in MO content before processing
     *****************************************************/    
    public function preprocess_MIC1()
    {   
        $this->MO->argument = trim($this->MO->argument);
        //Elimate wrong space
        $this->MO->argument = preg_replace('/\s*,\s*/', ',', $this->MO->argument);
        //Remove double space        
        $this->MO->argument = preg_replace('/\s{2,}/', ' ', $this->MO->argument);
        
        $temp = explode(',',$this->MO->argument);
        $this->MO->args = array();
        foreach ($temp as $coi)
        {
            //Correct secret number to format of xxx-xxx-xxx        
            //$coi = preg_replace('/^([0-9]{3,3})[- ]?([0-9]{3,3})[- ]?([0-9]{3,3})/', '$1-$2-$3', $coi);            
            
            $coi = explode(' ',$coi);
            $coi[1] = isset($coi[1])?$coi[1]:NULL;
            
            $temp = array();
            $temp['secret_number'] = $coi[0];
            array_shift($coi);
            $temp['oid'] = implode(' ',$coi);
            $this->MO->args[] = $temp;
        }
    }        

    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function active_cois()
    {
        $result = array();        
        
        foreach($this->MO->args as $coi)
        {            
            write_log('error',"Loading COI ".$coi['secret_number']);
            if ( ! $this->Coi_model->load_from_database($coi['secret_number'],array('secret_number','serial_number')))
            {                                
                $this->Evr->not_found[] = $coi['secret_number'];
                write_log('error',"COI ".$coi['secret_number']." not found!");
            }            
            else                        
            {                   
                if ($this->Coi_model->status == 'created')
                {
                    $this->Evr->not_found[] = $coi['secret_number'];       
                    write_log('error',"COI ".$coi['secret_number']." in status of 'created'. Change it to 'inactive' first!");             
                }
                elseif ($this->Coi_model->status != 'inactive')
                {
                    $this->Evr->activated[] = $this->Coi_model->secret_number;
                    write_log('error',"COI ".$coi['secret_number']." in status of '".$this->Coi_model->status."'. Cannot active!");
                }
                else
                {
                    //Check if oid available
                    if ($coi['oid'])
                    {
                        //Active the COI
                        $this->Coi_model->status = 'active';
                        $this->Coi_model->status_time = date('Y-m-d H:i:s');
                        $this->Coi_model->oid = $coi['oid'];
                        $this->Coi_model->update();         
                        
                        $this->Evr->successfully[] = $this->Coi_model->serial_number;     
                        write_log('error',"Active COI ".$this->Coi_model->serial_number." successfully");        
                    }
                    else
                    {
                        $this->Evr->miss_oid[] = $this->Coi_model->serial_number;
                        write_log('error',"Miss OID parameter to active COI ".$this->Coi_model->serial_number.".");
                    }
                }
            }
        }
    }
    
    public function preprocess_MIC2()
    {
        //Elimate wrong space
        $this->MO->argument = preg_replace('/\s*,\s*/', ',', $this->MO->argument);
        //Remove double space        
        $this->MO->argument = preg_replace('/\s{2,}/', ' ', $this->MO->argument);
                
        $this->MO->args = explode(',',$this->MO->argument);        
    }
    
    public function cancel_cois()
    {
        foreach($this->MO->args as $coi)
        {
            if ( ! $this->Coi_model->load_from_database($coi,array('secret_number','serial_number')))
            {
                $this->Evr->not_found[] = $coi;
                write_log('error',"COI ".$coi." not found!");
            }            
            else            
            {   
                if ($this->Coi_model->status == 'created')
                {
                    $this->Evr->not_found[] = $coi;         
                    write_log('error',"COI ".$coi." in status of 'created'. Change it to 'inactive' first!");           
                }
                elseif ($this->Coi_model->status == 'cancelled')
                {
                    $this->Evr->cancelled[] = $this->Coi_model->serial_number;
                    write_log('error',"COI ".$coi." in status of '".$this->Coi_model->status."'. Cannot cancel!");
                }
                else
                {
                    //Cancel the COI
                    $this->Coi_model->status = 'cancelled';
                    $this->Coi_model->status_time = date('Y-m-d H:i:s');                          
                    $this->Coi_model->update();      
                    
                    $this->Evr->successfully[] = $this->Coi_model->serial_number;           
                    write_log('error',"Cancel COI ".$this->Coi_model->serial_number." successfully");           
                }
            }
        }        
    }
    
    public function preprocess_MIC3()
    {
        $this->preprocess_MIC2();
    }
    
    public function lost_cois()
    {
        foreach($this->MO->args as $coi)
        {
            if ( ! $this->Coi_model->load_from_database($coi,array('secret_number','serial_number')))
            {
                $this->Evr->not_found[] = $coi;
                write_log('error',"COI ".$coi." not found!");
            }            
            else            
            {   
                if ($this->Coi_model->status == 'created')
                {
                    $this->Evr->not_found[] = $coi;     
                    write_log('error',"COI ".$coi." in status of 'created'. Change it to 'inactive' first!");               
                }
                elseif ($this->Coi_model->status == 'lost')
                {
                    $this->Evr->lost[] = $this->Coi_model->serial_number;
                    write_log('error',"COI ".$coi." in status of '".$this->Coi_model->status."'. Cannot mark as lost!");
                }
                else
                {
                    //Mark the COI as lost
                    $this->Coi_model->status = 'lost';
                    $this->Coi_model->status_time = date('Y-m-d H:i:s');                          
                    $this->Coi_model->update();      
                    
                    $this->Evr->successfully[] = $this->Coi_model->serial_number;          
                    write_log('error',"Mark as lost COI ".$this->Coi_model->serial_number." successfully");            
                }
            }
        }        
    }
}

/* End of file*/