<?php

class Dlr extends MX_Controller 
{    
	public function __construct()
	{
            parent::__construct();      
            $this->load->library('Environment_params',NULL,'Evr'); //For environmental parameters
        
            //For development;
            $this->output->enable_profiler(ENVIRONMENT == 'development');
            //Load config
            $this->load->config('core');
            $this->load->config(ENVIRONMENT);
	}
	
	public function process($status, $status_time, $mt_id)
	{
            set_log_source("DLR $mt_id");
            if ($this->config->item('max_resend_time')>0) //Try to resend MT
            {
                $this->load->model("Mt_model","MT_model");

                $props = $this->MT_model->get_mt($mt_id);
                if ( ! $props)
                {
                    write_log('error',"Got DLR message but could not find corresponding MT . MT ID = $mt_id");
                    return FALSE;
                }
                $this->load->library("Mt",NULL,'MT');
                $this->MT->load($props);  

                if ($status == 16 || $status == 2) //non-delivered to SMSC || non-delivered to phone
                {                          
                    if ($this->MT->resend < $this->config->item('max_resend_time')) //Should try to resend
                    {
                        write_log('error',"Severity: Warning  --> Cannot deliver MT to SMSC, retry to send it. MT_ID = $mt_id");

                        $this->MT_model->send($this->MT);                
                        $this->MT->resend++;     
                        $this->MT_model->update($this->MT);                                                   
                    }                                
                }	 

                //$this->MT->status = $status;
                //$this->MT->status_time = $status_time; 
                //$this->MT->actual_status_time = time();                                 
            }       


            //Update DLR
            $this->load->model('Dlr_model');
            $this->Dlr_model->update($mt_id,$status,$status_time);
	}
        
        public function migrade()
        {
            $result = TRUE; $i=0;
            while ($result)
            {
                $this->db->select('mt_id,status,time')->from('dlr');
                $this->db->limit(10000,$i*10000);
                $query = $this->db->get();$result = $query->result();
                foreach ($result as $row)
                {
                    $this->db->set('status',$row->status)
                            ->set('last_status_time',$row->time)
                            ->where('id',$row->mt_id);
                    $this->db->update('mt');
                }    
                $i++;
            }
        }
}

/* End of file xs.php */
/* Location: ./sms-services/controllers/xs.php */