<?php            
class Injector extends MX_Controller 
{
    
    var $url;
    
    function __construct()
    {
        parent::__construct();        
        $this->url = $this->config->item('base_url')."/"; //Development environment
        //For development;
        if (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing')
        {
            $this->output->enable_profiler(TRUE);
            write('<a href="http://appsrv.mvas.vn/'.ENVIRONMENT.'/sms-services/injector" target="_parent">SMS Injection Form</a>'); 
            write('<a href="http://appsrv.mvas.vn/'.ENVIRONMENT.'/sms-services/injector/lottery" target="_parent">Lottery Result Injection Form (KQXS)</a>');           
            write('<hr>');            
        }               
    }
        
    function index()
    {
        $data['time'] = date("Y-m-d H:i:s",time());
        $data['form_processing_url'] = $this->url."injector/inject";
    	$this->load->view('injector-form.html',$data);
    }
    
    function lottery()
    {
        $data['time'] = date("Y-m-d H:i:s",time());
        $data['form_processing_url'] = $this->url."injector/inject";
        
        $this->load->model('lottery/Lottery_model');
        $this->load->model('lottery/Result_model');
        $result = $this->Lottery_model->get_last_today_result('MB');        
        if ($result)
        {
            $result->content = str_replace("\n"," ",$result->content);
            $this->Result_model->parse_result_string($result->content);            
        }
        $weekday = date('w');
        $data['lottery_code'] = $this->Lottery_model->get_area();        
        $data['lottery_date'] = date('d/m');        
        $data['prizes'] = array(
            array(
                'title'=>'1',
                'count'=>1,
                'length'=>5,                            
            ),
            array(
                'title'=>'2',
                'count'=>2,
                'length'=>5            
            ),
            array(
                'title'=>'3',
                'count'=>6,
                'length'=>5            
            ),            
            array(
                'title'=>'4',
                'count'=>4,
                'length'=>4            
            ),            
            array(
                'title'=>'5',
                'count'=>6,
                'length'=>4          
            ),
            array(
                'title'=>'6',
                'count'=>3,
                'length'=>3           
            ),        
            array(
                'title'=>'7',
                'count'=>4,
                'length'=>2            
            ),            
            array(
                'title'=>'DB',
                'count'=>1,
                'length'=>5            
            )            
        );
        $data['result'] = $this->Result_model->result;
        
    	$this->load->view('lottery-injector-form.html',$data);        
    }
    
    function inject()
    {                       
        $content = $_POST['content'];
        $content = str_replace("\n"," ",$content);
        
        $this->load->helper('date');        
        $time = human_to_unix($_POST['time']);
        
        $valid = ($content != '') && ($_POST['msisdn'] != '') && ($_POST['short_code'] != '') && ($time != '');
        
        if ( ! $valid)
        {
            echo "You entered not enough information";
            return;
        } 

        // Redirect to lottery update link                    
        $this->load->helper('url');     
        redirect($this->config->item('base_url')."/service/exec/short_code/".urlencode($_POST['short_code']).'/msisdn/'.urlencode($_POST['msisdn']).'/time/'.$time.'/smsc_id/'.urlencode($_POST['smsc_id']).'/content/'.str_replace("%0A","+",urlencode($content)));                          
    }                     
    
    function reload()
    {
        $shell_result = shell_exec("sudo mysqldump --add-drop-table -pSec@6789ret mvas | mysql -pSec@6789ret dev_app");
        echo "done $shell_result";
    }
}

/* End of file Scheduler.php */
/* Location: ./sms-services/controllers/Scheduler.php */