<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class _KQMB extends MY_Controller {
            
    private $msg = array(); //store parsed msg to send in event of _after_udf    
    private $excluded_msisdn = array();
    
    //Cau hinh dau so nao tra giai nao voi dich vu XSTT.
    //Chu y moi phan tu deu phai co giai 'DB'
    private $return_prizes = array(
        '7027' => array('DB'),
        '7127' => array('DB'),
        '7227' => array('DB'),
        '7327' => array('4','DB'),
        '7427' => array('1','4','DB'),
        '7527' => array('1','2','3','4','5','6','7','DB'),
        '7627' => array('1','2','3','4','5','6','7','DB'),
        '7727' => array('1','2','3','4','5','6','7','DB'),
        '7827' => array('1','2','3','4','5','6','7','DB'),
        '7927' => array('1','2','3','4','5','6','7','DB')
    );    
     
    public function __construct()
    {
        parent::__construct();
        $this->load->model("Lottery_model");        
    }
    
    protected function __initialize()
    {
        if ($this->MO->argument)
        {
            $this->MO->date = $this->MO->argument;
        }
    }    
    
    private function _get_msg($prize = NULL)
    {       
        $prize = (is_null($prize))?$this->Evr->lottery['prize']:$prize;        
        if ( ! isset($this->msg[$prize]))
        {
            $this->msg[$prize] = $this->generate_mt_message('prize'.$prize,array('result'=>$this->Evr->lottery['result']));
        }
        
        return $this->msg[$prize];
    }
    
    protected function load_data($template)
    {
        $data = array();
        $data['short_code'] = $this->MO->short_code;
        $date = ($this->MO->date)?strtotime($this->MO->date):time();
        $date = date('d/m',$date);   
        $data['date'] = $date;        
          
        switch ($template)
        {                                                                           
        }
        
        return $data;
        
    }    
        
    //Kich ban khi cap nhat ket qua
    protected function sc_after_udf()
    {               
        if (($this->MO->date) && ($this->MO->date != date('Y-m-d')))
        {
            return;
        }             

        if ($this->service == 'XS')
        {            
            if ($this->Evr->lottery['prize'] == 'DB')
            {
                $this->reply_message($this->_get_msg($this->Evr->lottery['prize']));              
                $this->MO->balance -= 1;                
                $this->excluded_msisdn[] = $this->MO->msisdn;
            }
            return;            
        } 
        elseif ($this->service == 'XSTT')
        {
            $prizes = $this->return_prizes[$this->MO->short_code];

            if ((count($prizes) == 8) || (in_array($this->Evr->lottery['prize'],$prizes))) //count = 8 mean all
            {
                $this->reply_message($this->_get_msg($this->Evr->lottery['prize']));              
                $this->MO->balance -= 1;
            }                
        }
        elseif ($this->service == 'LOTO')
        {
            $this->reply_string($this->Evr->lottery['loto']);
            $this->MO->balance -= 1;   
            $this->excluded_msisdn[] = $this->MO->msisdn;
        }
        elseif ($this->service == 'LOTODaily')
        {
            $this->reply_string($this->Evr->lottery['loto']);
            $this->MO->balance -= 1;   
        }                         
        
    }

    public function __event_after_udf()
    {             
        //Giai DB
        if ($this->Evr->lottery['prize'] == 'DB')
        {
            /*
             * KQ Xo so
             */
            $this->excluded_msisdn = array();
            //Tra tin dich vu XS, KQ trong kich ban sc_after_udf ghi lai nhung MO da tra kq
            $this->service = 'XS';
            $keywords = $this->get_keywords('XS');
            $keywords = array_merge($keywords,$this->get_keywords('KQ'));
            $this->queue_db->where('mo_queue.smsc_id !=','Viettel');
            $this->process_queue(NULL,$keywords,NULL,array('mo_queue.msisdn'));     
            
            $this->service = 'XSTT';
            //Chi tra ket qua cho nhung MO nhan hom nay. MO con ton tu hom truoc xe de do de gui tin hello
            $this->queue_db->where('date(from_unixtime('.$this->queue_db->protect_identifiers('time').'))',$this->queue_db->escape(date('Y-m-d')),FALSE);
            if ($this->excluded_msisdn) $this->queue_db->where_not_in('mo_queue.msisdn',$this->excluded_msisdn);
            $keywords = $this->get_keywords('XSTT');
            //Neu la giai dac biet tra luon cho VIP, MM, SC, TK
            if ($this->Evr->lottery['prize'] == 'DB')
            {                        
                $keywords = array_merge($keywords,$this->get_keywords('VIP'));
                $keywords = array_merge($keywords,$this->get_keywords('MM'));
                $keywords = array_merge($keywords,$this->get_keywords('SC'));
                $keywords = array_merge($keywords,$this->get_keywords('TK'));
            }
            
            $this->queue_db->where('mo_queue.smsc_id !=','Viettel');
            
            $this->process_queue(NULL,$keywords,NULL,array('mo_queue.msisdn'));      
            
            /*
            * Ket qua trinh bay dang LOTO
            */ 
            $this->excluded_msisdn = array();
            //Tra dich vu loto
            $this->service = 'LOTO';
            $keywords = $this->get_keywords('LOTO');
            $this->queue_db->where('mo_queue.smsc_id !=','Viettel');
            $this->process_queue(NULL,$keywords);            
            //Cac dich vu LOTO khac
            $this->service = 'LOTODaily';
            //Chi tra ket qua cho nhung MO nhan hom nay. MO con ton tu hom truoc xe de do de gui tin hello
            $this->queue_db->where('date(from_unixtime('.$this->queue_db->protect_identifiers('time').'))',$this->queue_db->escape(date('Y-m-d')),FALSE);
            if ($this->excluded_msisdn) $this->queue_db->where_not_in('mo_queue.msisdn',$this->excluded_msisdn);
            $keywords = $this->get_keywords('VIP');
            $keywords = array_merge($keywords,$this->get_keywords('MM'));
            $keywords = array_merge($keywords,$this->get_keywords('SC'));
            $keywords = array_merge($keywords,$this->get_keywords('TK'));
            $this->queue_db->where('mo_queue.smsc_id !=','Viettel');
            $this->process_queue(NULL,$keywords,NULL,array('mo_queue.msisdn'));            
        }
        else //Khong phai giai dac biet
        {
            $this->service = 'XSTT';
            $short_codes = array();
            foreach ($this->return_prizes as $short_code => $prizes)
            {
                if (in_array($this->Evr->lottery['prize'],$prizes))
                {
                    $short_codes[] = $short_code;
                }
            }            
            if ($short_codes)
            {
                //Chi tra cho thang nhan hom nay
                $this->queue_db->where('date(from_unixtime('.$this->queue_db->protect_identifiers('time').'))',$this->queue_db->escape(date('Y-m-d')),FALSE);
                $keywords = $this->get_keywords('XSTT');
                $this->queue_db->where('mo_queue.smsc_id !=','Viettel');
                $this->process_queue($short_codes,$keywords,NULL,array('mo_queue.msisdn'));
            }
        }                       
    }       
}

/* End of file*/