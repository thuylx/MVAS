<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class XH extends MY_Controller
{    
    private $SBD;
    
    public function __construct()
    {
        parent::__construct();        
        //$this->load->model('Diem_model');
    }
    
    public function __parse_new_mo()
    {        
        $this->MO->argument = preg_replace('/[^ A-Za-z0-9]/','',$this->MO->argument);        
        $this->MO->argument = preg_replace('/\sSBD\s/',' ',$this->MO->argument);
        $this->MO->argument = trim($this->MO->argument);                            
    }
    
    public function __initialize()
    {
        if ($this->MO->argument == '')
        {
            $this->reply('sai');
            $this->scenario = ""; //Do no scenario
            return;
        }
        
        $args = explode(' ',$this->MO->argument);        
        if (count($args) < 1)
        {
            write_log("debug", "Missing argument SBD");
            if ($this->trigger == 'new_mo')
            {
                $this->reply("sai");
            }
            
            $this->scenario = ""; //Do no scenario
            return;
        }        
                
        $this->SBD = strtoupper($args[0]);       
    }
    
    protected function load_data($template)
    {
        $data['SBD'] = $this->SBD;
        return $data;        
    }
    
    protected function sc_7027()
    {
        $this->reply('sai_dau_so');
    }
    
    protected function sc_7127()
    {
        $this->sc_7027();
    }
    
    protected function sc_7227()
    {
        $this->sc_7027();
    }    
    
    protected function sc_7327()
    {
        $this->sc_7027();
    }    
    
    protected function sc_7427()
    {
        $this->sc_7027();
    }    
            
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {
        /*
        $data = $this->Diem_model->get_diem($this->ma_tinh,$this->SBD);        
        
        if ($data)
        {
            $this->reply('diem',$data);
            
            if ($this->trigger != 'new_mo')
            {
                $this->MO->balance = 0;
            }
            
            return;
        }
        $data = array('SBD'=>$this->SBD,'ma_tinh'=>$this->ma_tinh);
        if ($this->trigger == 'new_mo')
        {
            $this->MO->balance = 1;
            $this->reply('not_found',$data);
        }
        */
        
        $this->reply('xac_nhan');
        $this->MO->balance = 1;
    }
}

/* End of file*/