<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class DIEM extends MY_Controller
{
    private $ma_tinh;
    private $SBD;
    
    public function __construct()
    {
        parent::__construct();        
        $this->load->model('Diem_model');
    }
    
    public function __parse_new_mo()
    {
        write($this->MO->argument);
        $this->MO->argument = preg_replace('/[^ A-Za-z0-9]/','',$this->MO->argument);
        write($this->MO->argument);
        $this->MO->argument = preg_replace('/\sSBD\s/',' ',$this->MO->argument);
        write($this->MO->argument);        
    }
    
    public function __initialize()
    {
        $args = explode(' ',$this->MO->argument);
        if (count($args) < 2)
        {
            write_log("debug", "Missing argument ma_tinh or SBD");
            if ($this->trigger == 'new_mo')
            {
                $this->reply("missing_argument");
            }
            
            $this->scenario = ""; //Do no scenario
            return;
        }
        
        $this->ma_tinh = $this->Diem_model->verify_ma_tinh(strtoupper($args[0]));                
        if ( ! $this->ma_tinh)
        {
            write_log('debug','Incorrect ma_tinh');
            if ($this->trigger == 'new_mo')
            {
                $this->reply("ma_tinh_incorrect",array('ma_tinh'=>$args[0]));
            }
            
            $this->scenario = ""; //Do no scenario
            return;            
        }
                
        $this->SBD = strtoupper($args[1]);
        
    }
    
    protected function sc_7027()
    {
        $this->reply('short_code_incorrect');
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
    }
}

/* End of file*/