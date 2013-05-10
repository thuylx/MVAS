<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class DATN extends MY_Controller
{
    private $ten_mon = '';
    private $ma_de = '';
    
    public function __construct()
    {
        parent::__construct();        
        $this->load->model('Da_model');
        $this->load->helper('core');
    }
    
    public function __parse_new_mo()
    {
        $this->MO->auto_correct(get_mo_correction_rules('datn'));
    }
    
    public function __initialize()
    {
        $args = explode(' ',$this->MO->argument);
        if (count($args) < 1 || $args[0] == '')
        {
            write_log("debug", "Missing argument ten_mon or ma_de");
            $this->reply("missing_argument");
            $this->scenario = ""; //Do no scenario
            return;
        }
        
        $this->ten_mon = strtoupper($args[0]);
        if (isset($args[1]))
        {
            $this->ma_de = strtoupper($args[1]);
        }                
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
        $dap_an = $this->Da_model->get_dap_an($this->ten_mon,$this->ma_de);
        
        $data['ten_mon'] = $this->ten_mon;
        $data['ma_de'] = ($this->ma_de)?", ma de $this->ma_de":"";        
        if ($dap_an)
        {
            $data['dap_an'] = $dap_an;
            $this->reply('dap_an',$data);
            
            if ($this->trigger != 'new_mo')
            {
                $this->MO->balance = 0;
            }
            
            return;
        }
        
        if ($this->trigger == 'new_mo')
        {
            $this->MO->balance = 1;
            $this->reply('not_found',$data);
        }
    }
}

/* End of file*/