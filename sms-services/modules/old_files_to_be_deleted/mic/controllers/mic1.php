<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MIC1 extends MY_Controller
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
        $this->MO->argument = trim($this->MO->argument);
        //Elimate wrong space
        $this->MO->argument = preg_replace('/\s*,\s*/', ',', $this->MO->argument);
        //Remove double space        
        $this->MO->argument = preg_replace('/\s{2,}/', ' ', $this->MO->argument);
        
        $temp = explode(',',$this->MO->argument);
        foreach ($temp as $coi)
        {
            //Correct secret number to format of xxx-xxx-xxx        
            //$coi = preg_replace('/^([0-9]{3,3})[- ]?([0-9]{3,3})[- ]?([0-9]{3,3})/', '$1-$2-$3', $coi);            
            
            $coi = explode(' ',$coi);
            $coi[1] = isset($coi[1])?$coi[1]:NULL;
            
            $this->cois[] = array(
                'secret_number' => $coi[0],
                'oid'           => $coi[1]
            );
        }
    }        

    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {
        $result = array();        
        
        foreach($this->cois as $coi)
        {            
            write_log('error',"Loading COI ".$coi['secret_number']);
            if ( ! $this->Coi_model->load_from_database($coi['secret_number'],array('secret_number','serial_number')))
            {                                
                $result['not_found'][] = $coi['secret_number'];
                write_log('error',"COI ".$coi['secret_number']." not found!");
            }            
            else                        
            {                   
                if ($this->Coi_model->status == 'created')
                {
                    $result['not_found'][] = $coi['secret_number'];       
                    write_log('error',"COI ".$coi['secret_number']." in status of 'created'. Change it to 'inactive' first!");             
                }
                elseif ($this->Coi_model->status != 'inactive')
                {
                    $result['activated'][] = $this->Coi_model->secret_number;
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
                        
                        $result['successfully'][] = $this->Coi_model->serial_number;     
                        write_log('error',"Active COI ".$this->Coi_model->serial_number." successfully");        
                    }
                    else
                    {
                        $result['miss_oid'][] = $this->Coi_model->serial_number;
                        write_log('error',"Miss OID parameter to active COI ".$this->Coi_model->serial_number.".");
                    }
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
                
        $return = array();
        $order = array('successfully','activated','not_found','miss_oid');
        foreach ($order as $item)
        {
            if (isset($result[$item]))
            {
                $temp = $this->generate_mt_message($item,array($item=>$result[$item]));
                $return[$item] = $temp['content']; 
            }
        }
        $return = implode("\n",$return);    
            
        $this->reply_string($return);        
    }           
}

/* End of file*/