<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stat extends MY_Controller
{       
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Stat_model');        
    }
    
    protected function sc_7027()
    {
        $this->reply_string("Sai dau so dich vu\n\nDe xem thong ke MO va doanh thu hom nay, soan STAT gui 7527");
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
        //Argument processing
        $time = time();
        $args = strtolower($this->MO->argument);
        if ($args)
        {
            $args = explode(" ",$args);
            
            foreach ($args as $arg)
            {
                switch ($arg)
                {
                    case 'dso':
                    case 'dauso':
                    case 'sc':                        
                        $this->Stat_model->group_by('short_code');
                        break;
                        
                    case 'date':
                    case 'ngay':
                        $this->Stat_model->group_by('date');
                        break;            
                        
                    case 'smsc':
                    case 'telco':
                    case 'mang':
                    case 'nhamang':
                        $this->Stat_model->group_by('smsc_id');
                        break;                                
                        
                    case 'keyword':
                    case 'dvu':
                    case 'dichvu':
                        $this->Stat_model->group_by('keyword');
                        break;                    
                }
                
                if (is_numeric($arg))
                {
                    $time = $time - $arg*24*60*60;
                }
            }
        }
        
        $this->Stat_model->set_filter('date',date('Y-m-d',$time));
        $this->Stat_model->set_filter('`smsc_id`<>',"'GSM-Modem'",FALSE);
        $this->Stat_model->set_filter('`smsc_id`<>',"'GSM-Modem-2'",FALSE);
        $stat = $this->Stat_model->get_statistic();
        
        if ( ! $stat)
        {
            $this->reply_string("Khong co tin nhan nao.");
            return;
        }
        
        $row = $stat[0];
        $keys = array_keys($row);        
        $content = implode('-',$keys);     
        
        $searches = array('smsc_id','short_code','keyword','revenue');
        $replacements = array('telco','dso','dvu','dthu');
        $content = str_replace($searches,$replacements,$content);
           
        reset($stat);
        
        $mo=0;$mt=0;$reveue=0;
        foreach($stat as $row)
        {
            $row['revenue'] = round($row['revenue']/1000);
            $content .= "\n".implode('-',$row);
            $mo += $row['mo'];
            //$mt += $row['mt'];
            $reveue += $row['revenue'];
        }
        if (count($stat)>1)
        {
            $content .= "\n+: $mo-$reveue";            
        }
        
        $this->reply_string($content);
    }             
}

/* End of file*/