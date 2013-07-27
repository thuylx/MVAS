<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MIC3 extends MY_Controller
{
    private $cois;
    
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
    public function __initialize()
    {
        //Elimate wrong space
        $this->MO->argument = preg_replace('/\s*,\s*/', ',', $this->MO->argument);
        //Remove double space        
        $this->MO->argument = preg_replace('/\s{2,}/', ' ', $this->MO->argument);
        
        $this->cois = explode(',',$this->MO->argument);
    }        

    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {
        $result = array();           
        
        foreach($this->cois as $coi)
        {
            if ( ! $this->Coi_model->load_from_database($coi,array('secret_number','serial_number')))
            {
                $result['not_found'][] = $coi;
                write_log('error',"COI ".$coi." not found!");
            }            
            else            
            {   
                if ($this->Coi_model->status == 'created')
                {
                    $result['not_found'][] = $coi;     
                    write_log('error',"COI ".$coi." in status of 'created'. Change it to 'inactive' first!");               
                }
                elseif ($this->Coi_model->status == 'lost')
                {
                    $result['lost'][] = $this->Coi_model->serial_number;
                    write_log('error',"COI ".$coi." in status of '".$this->Coi_model->status."'. Cannot mark as lost!");
                }
                else
                {
                    //Mark the COI as lost
                    $this->Coi_model->status = 'lost';
                    $this->Coi_model->status_time = date('Y-m-d H:i:s');                          
                    $this->Coi_model->update();      
                    
                    $result['successfully'][] = $this->Coi_model->serial_number;          
                    write_log('error',"Mark as lost COI ".$this->Coi_model->serial_number." successfully");            
                }
            }
        }
        
        foreach ($result as $key=>$value)
        {
            $count[$key] = count($value);
            $value = implode(', ',$value);
            $result[$key] = $value;            
        }
        
        $this->Evr->count = $count;
                
        $order = array('successfully','lost','not_found');
        foreach ($order as $item)
        {
            if (isset($result[$item]))
            {
                $temp = $this->generate_mt_message($item,array($item=>$result[$item]));
                $result[$item] = $temp['content']; 
            }
        }
        $result = implode("\n",$result);    
            
        $this->reply_string($result);
    }           
}

/* End of file*/