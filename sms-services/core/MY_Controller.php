<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MY_Controller extends MX_Controller
{   
    //to have acess independantly from other controller
    public $MO;
    
    //Use to control end and break step in scenario if any
    public $action_end = FALSE;
    public $action_break = -1;
    public $action_jump = -1;
    
    public $parsing_cache = array();
    public $parsing_cache_started = FALSE;
    
    public $soap_client;
    //public $data = array();

    public function __construct()
    {
        write_log('debug',"Constructing ".get_class($this),'core');        
        parent::__construct();        
        $this->MO = new Mo();
//        $this->MT->mo_id = 0; //NULL in database mean from external application
        
        set_time_limit($this->config->item('time_limit'));                
        $this->load->model('service_actions_model');
        $this->load->helper('xml');
    }
            
    /**
     * Default method which will be called from main service controller
     * */
    public function index()
    {
        //-----------------------------------------------------------------------------------------
        //Load global environmental parameters
        //-----------------------------------------------------------------------------------------          
        $this->Evr->scp = $this->scp;        
        
        //-----------------------------------------------------------------------------------------
        //Process
        //-----------------------------------------------------------------------------------------        
        //Try to call event processing function event_<event_name> first
        // if failure call process_queue for relevant queued MO
        $method = strtolower("__event_".$this->scp->trigger);
        if (method_exists($this,$method))
        {
            $this->$method();
        }
        else
        {
            //$this->queue_db->where_in('keyword',$this->get_keywords($this->scp->service));
            //$this->process_queue();
            $this->process($this->scp->root_service_action);
        }

        //-----------------------------------------------------------------------------------------
        //Raise event after_<service>
        //-----------------------------------------------------------------------------------------
        //$this->Event->raise("after_$service");
    }
    
    public function start_parsing_cache()
    {
        $this->parsing_cache_started = TRUE;
    }
    
    public function stop_parsing_cache()
    {
        $this->parsing_cache_started = FALSE;
    }
    
    public function clear_parsing_cache()
    {
        $this->parsing_cache = array();
    }
    
    public function load_data($var)
    {
        //Abstract function only
        return NULL;
    }   
    
    public function load_time($format)
    {
        return array($format => date($format));
    }              
    
    public function load_mo()
    {
        $args = func_get_args();
        if ($args)
        {
            switch ($args[0])
            {
                case 'arrival_time':
                    $this->MO->arrival_time[$args[1]] = date($args[1],$this->MO->time);
                    break;

                case 'msisdn_no_plus':
                    $this->MO->msisdn_no_plus = trim($this->MO->msisdn,'+');
                    break;
            }            
        }
        
        $this->MO->args = array();
        $method = 'preprocess_'.$this->scp->service;
        if (method_exists($this, $method))
        {
            $this->$method();
        }        
        else
        {
            $this->MO->args = array();
            $this->MO->args = explode(' ', $this->MO->argument);
        }
        
        return $this->MO;
    }  
    
    public function count($var)
    {
        return count($this->Evr->$var);
    }
    
    public function is_exist($var)
    {
        if (property_exists($this->Evr, $var))
        {
            return TRUE;       
        }         
        
        $var = explode('.',$var);
        if (count($var) == 1)
        {
            return FALSE;
        }
        
        $obj = $this->Evr;
        foreach ($var as $name)
        {
            if (is_object($obj)) 
            {
                if (property_exists($obj, $name) || isset($obj->$name) || !is_null($obj->$name))
                {
                    $obj = $obj->$name;                    
                }                
                else
                {
                    return FALSE;
                }
            }
            elseif (is_array($obj)) 
            {
                if (key_exists($name,$obj))
                {
                    $obj = $obj[$name];
                }
                else
                {
                    return FALSE;
                }
            }
            else
            {                
                return FALSE;           
            }
        }        
        return TRUE;
    }
    
    /**
     *Function to check if a MT sent in a recent period by a service action
     * @param interger $action_id
     * @param interger $minutes
     * @param string $msisdn: NULL meansz msisdn of current processing MO     
     * @return interger 
     */
    public function is_sent($action_id, $minutes = NULL, $msisdn = NULL)
    {        
        $msisdn = (is_null($msisdn))?$this->MO->msisdn:$msisdn;
        $begin_time = time() - $minutes*60; //$minutes' before        
        return $this->MT_model->is_exist($action_id,array($msisdn),date('Y-m-d H:i:s',$begin_time));        
    } 
    
    /*
     * Get data for a var into array or object
     * @param   string    
     * @param   array/object
     */
    public function load_evr_param($var)
    {        
        preg_match('/(([0-9A-Za-z_]+)(\((.*)\))?)((\.[0-9A-Za-z_\-]+)*)/', $var,$matches);
        $is_func = $matches[3];
        $var_name = $matches[1];              
        $method = $matches[2];        
        $params = $matches[4];        
        $extension = $matches[5];
        if ( ! property_exists($this->Evr, $var_name))
        {                           
            //preg_match('/([0-9A-Za-z_]+)\((.*)\)/', $param[0],$matches);                                
            if ($is_func)
            {                                        
                $params = explode(',', $params);
                $temp = array();
                foreach ($params as $item)
                {
                    $item = trim($item);
                    if ($item=='NULL')
                    {
                        $item = NULL;
                    }
                    elseif ($item == 'FALSE')
                    {
                        $item = FALSE;
                    }
                    elseif ($item == 'TRUE')
                    {
                        $item = TRUE;
                    }
                    else
                    {
                        $item = preg_replace("/^\'(.*)\'$/",'$1',$item);
                    };
                    $temp[] = $item;
                }
                $params = $temp;
            }
            else
            {
                $method = 'load_'.$method;
                $params = explode('.', $extension); unset($params[0]);
            }            
            if (method_exists($this, $method))
            {                                                
                $temp = call_user_func_array(array($this,$method), $params);                            
            }
            elseif (function_exists($method))
            {
                $temp = call_user_func_array($method, $params);
            }
            else
            {
                $temp = $this->load_data($method,$params);                
            }                        

            if (is_null($temp))
            {
                write_log('error','WARNING: {'.$var.'} is NULL');
            }

            //Check if it should be array
            if ($extension && !(is_array($temp) || is_object($temp)))
            {
                write_log('error',"WARNING: Array or object is expected for varian of {".$var."}");
                $this->Evr->$var = $temp;
            }
            else
            {                        
                $this->Evr->$var_name = $temp;
            }             
        }
        elseif ($extension && (!is_object($this->Evr->$var_name) && !is_array($this->Evr->$var_name)))
        {
            $this->Evr->$var = $this->Evr->$var_name;
        }
    }
    
    public function parse($str, $adapt_data_type = FALSE)
    {           
        //Parse global variable first
//        do
//        {
//            $count = 0;
//            $str = $this->parser->parse($str,$this->Evr,$count);            
//        }while($count);
        
        //Lookup additional vars
        do {            
            $vars = $this->parser->get_vars_list($str);
            //Get data
//            $data = array();
            foreach($vars as $var)
            {
                $var = (substr($var, 0, 1) == '$')?substr($var, 1):$var;
                /*
                $var_name = '';
                $is_silent = FALSE;
                $param = explode('=', $var);
                if (count($param) == 2)
                {
                    $data[$var] = '';
                    $var_name = trim($param[0]);                    
                    $var = trim($param[1]);                    
                }
                 * 
                 */
                $this->load_evr_param($var);
            }                        
            
            $count = 0;
            $str = $this->parser->parse($str, $this->Evr, $count);
        } while ($count);          
        return $str;
    }
    
    public function _exec_service_action($action)
    {
        //write_log('debug','Checking action #'.$action['id'].': '.$action['title'].' ('.$action['type'].')');
        //--------------------------------------------------------------------------------------------------
        // Check expression. DO NOT USE PARSING CACHE HERE
        //--------------------------------------------------------------------------------------------------              
        if ($action['expression'])
        {
            write_log('debug','<i>Checking action expression #'.$action['id'].': '.$action['title'].' ('.$action['type'].')</i>');
            //write_log('debug','<i>Evaluating action expression</i>');
            $action['expression'] = $this->parse($action['expression']);
            if ( ! $this->expression->evaluate($action['expression']))
            {
                write_log('debug', 'Action Expression return FALSE, discarded.');
                return;
            }
        }
        //--------------------------------------------------------------------------------------------------
        
        if ($this->action_break >= 0)
        {
            $this->action_break -= 1;
            return; //do nothing
        }
        
        if ($this->action_end)
        {
            return; //do nothing
        }                
        //$action['input'] = htmlspecialchars_decode($action['input']); //Since fuel CMS always encode fields

        //--------------------------------------------------------------------------------------------------
        // Pre-process input for service action
        //--------------------------------------------------------------------------------------------------      
        if (key_exists($action['id'], $this->parsing_cache))
        {
            //Cached already
            $action['input'] = $this->parsing_cache[$action['id']];
        }
        else
        {         
            $action['input'] = $this->parse($action['input']);
            if ($this->parsing_cache_started)
            {
                $this->parsing_cache[$action['id']] = $action['input'];
            }            
        }
        //--------------------------------------------------------------------------------------------------

        //Logging
        if ($action['type'] != 'check' && $action['type'] != 'group')
        {
            write_log('debug','<b><i>'.highlight_scenario($action['type'].' #'.$action['id'].': '.$action['title']).'</i></b>');
        }        
        elseif ($action['type'] == 'group')
        {
            write_log('debug','<u>'.$action['type'].' #'.$action['id'].': '.$action['title'].'</u>');
        }
        else
        {
            write_log('debug','<i>'.$action['type'].' #'.$action['id'].': '.$action['title'].'</i>');
        }
        //--------------------------------------------------------------------------------------------------
        // Execute scenario actions
        //--------------------------------------------------------------------------------------------------
        $result = FALSE;
        switch ($action['type'])
        {
            case 'group':
                //Do nothing, just used to group actions together
                $result = TRUE; 
                break;
            
            case 'check':
                $result = $this->expression->evaluate($action['input']);     
                if ($result)
                    write_log('debug','<b><i>'.highlight_scenario('Check #'.$action['id'].': \''.$action['title'].'\' return TRUE').'</i></b>');
                break;
            
            case 'send_sms':                    
                @$sms = simplexml_load_string(xml_ampersand_escape($action['input']));                    
                if ($sms === FALSE)
                {
                    $sms = new stdClass;
                    $sms->content = $action['input'];
                }                    
                $sms->service_action_id = $action['id'];
                $result = $this->send_sms($sms);
                $this->MO->last_provision_time = time();
                break;
                
            case 'wap_push':
                @$wap = simplexml_load_string(xml_ampersand_escape($action['input']));                    
                if ($wap === FALSE)
                {
                    $wap = new stdClass;
                    $wap->content = $action['input'];
                    $wap->link = $action['input'];
                }
                $wap->service_action_id = $action['id'];
                $result = $this->wap_push($wap);     
                $this->MO->last_provision_time = time();
                break;
                
            case 'change_balance':
                $action['input'] = ($action['input']=='')?1:$action['input'];
                if (!is_numeric($action['input']))
                {
                    write_log('error','Invalid balance change value of '.$action['input'].' for step '.$action['id'].': '.$action['title']);                    
                    $result = FALSE;
                }                    
                else
                {
                    $this->MO->balance += $action['input'];
                    $result = TRUE;
                }                    
                break;
                
            case 'update_mo':
                @$sms = simplexml_load_string(xml_ampersand_escape($action['input']));                    
                if ($sms === FALSE)
                {
                    write_log('error', 'Update MO failed');
                } 
                
                $sms = $this->object_to_array($sms);
                foreach($sms as $key=>$value)
                {
                    $this->MO->$key = $value;
                }                  
                
                break;
                
            case 'run_service':
                if ($this->scp->load_service($action['input']))
                {
                    //$this->scp->trigger = $this->scp->service;
                    $this->scp->run_service();
                }
                break;
                
            case 'run_action':
                if ($action['input'])
                {
                    $input = $this->service_actions_model->get_action($action['input']);
                    if ($input) $this->_exec_service_action($input);                    
                }
                else
                {
                    write_log('error', 'WARNING: Invalid input to run_action. Discarded.');
                }
                break;
            
            case 'raise_event':
                $this->Event->raise($action['input']);
                break;
            
            case 'function':
                if ($action['input'] == '')
                {
                    write_log('debug', 'Function name is empty, do nothing');
                    $result = FALSE;
                    break;
                }
                
                @$func = simplexml_load_string(xml_ampersand_escape($action['input']));                                
                if ($func === FALSE)
                {
                    $func = $action['input'];
                    if (method_exists($this, $func))
                    {
                        $result = $this->$func();
                        $this->Evr->$func = $result;
                        break;
                    }
                    else
                    {
                        write_log('error', 'Function '.$func.' does not exist');
                        $result = FALSE;
                        break;
                    }
                }
                else
                {
                    $func = $this->object_to_array($func);
                    $result = @call_user_func_array(array($this,$func['name']), $func['params']);                   
                    $this->Evr->$func = $result;
                }                
                $result = ($result !== FALSE)?TRUE:FALSE;
                if (! $result)
                {
                    write_log('debug','Call function return false or function does not exist');
                }                
                break;
                
            case 'process_queue':       
                @$input = simplexml_load_string(xml_ampersand_escape($action['input']));
                if ($action['input'] != '' && $input === FALSE)
                {
                    write_log('error', 'Invalid xml expression', 'core');
                    $result = FALSE;
                    break;
                }
                if ($input !== FALSE)
                {                   
                    /*
                    if (property_exists($input, 'sub_query'))
                    {
                        foreach ($input->sub_query->children() as $sub_name=>$sub_funcs)
                        {
                            foreach($sub_funcs->children() as $func=>$params)
                            {
                                $params = $this->object_to_array($params);
                            //}
                                if ($func) call_user_func_array(array($this->queue_db,$func), $params);                                
                            }
                            $this->Evr->$sub_name = $this->queue_db->_compile_select();
                            $this->queue_db->_reset_select();
                            var_dump($this->Evr->$sub_name);
                        }
                    }
                     * 
                     */
                    
                    if (property_exists($input,'active_record'))
                    {
                        foreach($input->active_record->children() as $func=>$params)
                        {            
                            //foreach ($params->children() as $param)
                            //{
                                $params = $this->object_to_array($params);
                            //}
                            if ($func) call_user_func_array(array($this->queue_db,$func), $params);
                        }                                  
                    }
                    if (property_exists($input,'service'))
                    {
                        $keywords = array();                         
                        if (!$input->service->children()) 
                        {                            
                            $keywords = $this->get_keywords((string)$input->service);
                        }
                        else
                        {
                            foreach ($input->service->children() as $item)
                            {    
                                $keywords = array_merge($keywords,$this->get_keywords((string)$item));                            
                            }                            
                        }
                        $this->queue_db->where_in('keyword',$keywords);
                    }
                    
                }            

                $this->process_queue($action['id'],TRUE);
                return TRUE;    
                
            case 'loop':                                
                //Get input data
                if ($action['input'] != '')
                {
                    @$loop = simplexml_load_string(xml_ampersand_escape($action['input']));
                    if ($loop === FALSE)
                    {                        
                        write_log('debug','No xml found, cheat input as an array name');
                        $array = $action['input'];
                        $key = 'key';
                        $value = 'value';
                    }
                    else
                    {                        
                        if (isset($loop->array))
                        {
                            $array = (string)$loop->array;
                            $key = (isset($loop->key) && (string)$loop->key != '')?(string)$loop->key:'key';
                            $value = (isset($loop->value) && (string)$loop->value != '')?(string)$loop->value:'value';
                        }
                        else
                        {
                            $counter = (string)$loop->counter;
                            $from = (isset($loop->from) && is_integer((int)$loop->from))?(int)$loop->from:0;                    
                            $to = (int)$loop->to;    
                            $step = (isset($loop->step) && is_integer((int)$loop->step))?(int)$loop->step:1;                            
                        }  
                    }
                }                
                
                if (isset($array) && $array != '')
                {                    
                    if ( ! isset($this->Evr->$array)) $this->load_evr_param($array,$this->Evr);
                    foreach ($this->Evr->$array as $this->Evr->$key => $this->Evr->$value)
                    {                                 
                        $this->_exec_children_actions($action['id']);       
                    }   
                    return TRUE;                    
                }
                elseif(isset($counter) && isset($to) && $counter != '' && $to != '')
                {                
                    for ($this->Evr->$counter = $from;$this->Evr->$counter <= $to; $this->Evr->$counter += $step)
                    {
                        $this->_exec_children_actions($action['id']);
                    }                    
                    return TRUE;
                }
                else
                {
                    write_log('error', 'Invalid loop defination or array/object not found');
                    return FALSE;                    
                }
                
            case 'break':
                $action['input'] = ($action['input']=='')?1:$action['input'];
                if (!is_numeric($action['input']))
                {
                    write_log('error','Invalid break value for step '.$action['id'].': '.$action['title'].' Set to 1.');
                    $action['input'] = 1;
                }                    
                $this->action_break = $action['input'];
                return 'break';                    
                
            case 'jump':
                $this->action_end = TRUE;
                $this->action_jump = $action['input'];
                return 'end';
                
            case 'end':
                $this->action_end = TRUE;
                return 'end';     
                
            case 'set_environment':
                @$input = simplexml_load_string(xml_ampersand_escape($action['input']));
                if ($input === FALSE)
                { 
                    write_log('error', 'Set environemntal parameters failed');
                    $result = FALSE;
                    break;
                }
                //$input = $this->object_to_array($input);                
                foreach ($input->children() as $key=>$value)
                {                    
                    $type = (string)$value['type'];     
                    if (!$type) $type = 'string';
                    switch ($type)
                    {
                        case 'object':
                            $this->Evr->$key = (object)$this->object_to_array($value);                            
                            break;
                        case 'array':          
                            if (!property_exists($this->Evr,$key)) $this->Evr->$key = array();
                            $this->Evr->$key = array_merge($this->Evr->$key, $this->object_to_array($value));                            
                            break;
                        case 'int':
                            $this->Evr->$key = (int)$value;
                            break;
                        case 'expression':      
                            $math = create_function("", "return (".(string)$value.");" );
                            $this->Evr->$key = 0 + $math();                            
                            break;
                        case 'string':
                            $this->Evr->$key = (string)$value;
                            break;
                        case 'function':                           
                            if (property_exists($value, 'name')) //XML
                            {
                                $value = $this->object_to_array($value);
                                if (!key_exists('params',$value))
                                {
                                    $value['params'] = NULL;
                                }
                                $this->Evr->$key = @call_user_func_array(array($this,$value['name']), $value['params']);                                
                            }
                            else
                            {            
                                $value = (string)$value;
                                if (method_exists($this, $value))
                                {
                                    $this->Evr->$key = $this->$value();
                                }
                                else
                                {
                                    write_log('error', 'Function '.$value.' does not exist');
                                    $this->Evr->$key = FALSE;
                                }                                
                            }
                            break;
                    }                                                    
                    
                    write_log('debug', "Set environmental param: ($type)$key = ".var_export($this->Evr->$key,TRUE));                   
                }
                $result = TRUE;
                break;
                
            case 'auto_correct':
                @$rule = simplexml_load_string(xml_ampersand_escape($action['input']));
                if ($rule !== FALSE)
                {
                    $rule = $this->object_to_array($rule);
                    $rules = array($rule);
                    $this->MO->auto_correct($rules);
                }
                else
                {
                    $rule_codes = explode(',', $action['input']);               
                    foreach ($rule_codes as $code)
                    {
                        $code = trim($code);
                        $this->MO->auto_correct($this->MO_model->get_auto_correct_rules($code));
                    }
                    if ($this->MO->is_changed('keyword'))
                    {                        
                        $svc = $this->Service_model->get_service($this->MO->keyword);
                        if ( ! $svc)
                        {
                            $this->MO->status = "error_mo_invalid";
                        }
                        else
                        {
                            $this->scp->service = $svc->service;
                        }                               
                    }                    
                }
                break;
                
            case 'start_parsing_cache':
//                $this->parsing_cache_started = TRUE;
                $this->start_parsing_cache();
                break;
            
            case 'stop_parsing_cache':                
//                $this->parsing_cache_started = FALSE;
                $this->stop_parsing_cache();
                break;
            
            case 'create_soap_client':
                if (!$action['input'])
                {
                    write_log('error', 'Invalid url for soap client initiation');
                    $result = FALSE;
                    break;
                }
                try 
                { 
                    $this->soap_client = @new SoapClient($action['input']);
                    $result = TRUE;
                } catch (SoapFault $E){            
                    write_log('error','SOAPClient Failed: '.$E->faultstring);
                    $result = FALSE;
                    break;                        
                }                 
                break;
            
            case 'soap_call':
                if (!is_object($this->soap_client))
                {
                    write_log('error', 'Soap client has to be created first before perform soap call', $item);
                    $result = FALSE;
                    break;
                }
                if ($action['input'] == '')
                {
                    write_log('debug', 'Function name is empty for soap call, do nothing');
                    $result = FALSE;
                    break;
                }
                
                @$func = simplexml_load_string(xml_ampersand_escape($action['input']));                                
                if ($func === FALSE)
                {
                    $func = array();
                    $func['name'] = $action['input'];
                    $func['params'] = array();
                }
                else
                {                    
                    $func = $this->object_to_array($func);
                }                
                
                try 
                {
                    $evr = $func['name'];
                    $this->Evr->$evr = @$this->soap_client->__soapCall((string)$func['name'],(array)$func['params']);                                        
                    $result = TRUE;
                }
                catch (SoapFault $E)
                {
                    write_log('error','SOAP call failed: '.$E->faultstring);
                    $result = FALSE;
                    break;
                }                
                break;
            
            case 'write_log':
                write_log('debug', $action['input']);
                $result = TRUE;
                
            default:
                $result = TRUE;
        }
        if ($result === FALSE) return FALSE;        
        
        
        //--------------------------------------------------------------------------------------------------
        // Execute children actions
        //--------------------------------------------------------------------------------------------------
        
        $result = $this->_exec_children_actions($action['id']);
        
        return $result;
    }
    
    public function _exec_children_actions($action_id)
    {
        //Get children
        $children = $this->service_actions_model->get_children_list($action_id);
        foreach ($children as $child)
        {
            $result = $this->_exec_service_action($child);
            if ($result === 'break')
            {
                $this->action_break -= 1;
                if ($this->action_break >= 0) return $result;
                return;
                
            }
            if ($result === 'end') return $result;
        }
        
        return TRUE;
    }
    
   public function process($root_service_action_id = NULL, $exclude_root_service_action = FALSE)
   {
        //===============================================================================
        // Run service scenario
        //===============================================================================
        if (!$root_service_action_id) $root_service_action_id = 0;
        $this->action_jump = $root_service_action_id;
        $count = 0;
        while ($this->action_jump != -1 and $count<100)
        {          
            $root_service_action_id = $this->action_jump;
            $this->action_jump = -1;
            $count += 1;            
            if ($count==1 && $exclude_root_service_action)
            {                
                $this->_exec_children_actions($root_service_action_id);  
            }
            else
            {
                $action_root = $this->service_actions_model->get_action($root_service_action_id);
                if (!$action_root)
                {
                    write_log('error','Root service action not found, do nothing!');            
                }
                else
                {
                    $this->_exec_service_action($action_root);            
                }        
            }

            $this->action_break=-1;
            $this->action_end = FALSE;            
        }
        
        if ($count>=100) write_log('error', 'Exceed 100 loop because of jump');         
   }
           

    /**
     * Process given MO object
     * @param   object $MO: MO object to be processed
     * @param   interger $root_service_action_id is id of service action from which scenario start
     * @param   boolean $exclude_root_service_action
     * */
    public function process_mo(&$MO = NULL, $root_service_action_id = NULL, $exclude_root_service_action = FALSE)
    {
        //Load MO
        if ( ! is_null($MO))
        {
            $this->MO = $MO;                        
        }                      

        //===============================================================================
        // Initialize default MT based on MO.
        //===============================================================================        
        //Store current default MT infomation to be recovered after processing MO
        $MT_temp = $this->MT_Box->get_default_MT()->get_all_properties();
        
        //$this->MT->reply_to($this->MO);        
        $this->MT_Box->set_default_MT(array(
                                        'mo_id'=>$this->MO->id,
                                        'keyword'=>$this->MO->keyword, //This field is used for statistic feature.
                                        'smsc_id'=>$this->MO->smsc_id,
                                        'short_code'=>$this->MO->short_code,
                                        'msisdn'=>$this->MO->msisdn,   
                                        'template_id'=>NULL                               
                                        ));
        
        //Preprocess MO
        $this->load_mo();

        //Set environmental parameters          
        $this->Evr->mo = $this->MO;
        
        //PROCESS MO        
        $this->process($root_service_action_id, $exclude_root_service_action);

        //Cache processed MO to update later
        if ($this->MO->is_changed())
        {
            $this->MO->save();
        }
        else
        {
            write_log('debug',"Nothing have been changed with MO id =".$this->MO->id.".",'core');
        }

        //Recover default MT information after processing MO
        $this->MT_Box->set_default_MT($MT_temp);
    }    
    
    /**
     * Process relevant queued MO messages
     * @param   interger root_service_action_id is id of service actio from which service scenario run
     * @param   boolean exclude_root_service_action wheather or not by pass root service action
     * */
    public function process_queue($root_service_action_id, $exclude_root_service_action = TRUE)
    {                   
        while( ! ($this->MO_Queue->EOQ()))
        {            
            foreach($this->MO_Queue->get_queue() as $item)
            {                   
                $this->MO->load($item);                
                write_log('debug',"Processing MO id = ".$this->MO->id." from ".$this->MO->msisdn." to ".$this->MO->short_code,'core');
                //===============================================================================
                // Reset scenario
                //===============================================================================	                                                           
                $this->process_mo($this->MO,$root_service_action_id,$exclude_root_service_action);
            }
        }
        
        //Free mem
        $this->MO_Queue->reset();                
    }
    
    
    /**
     * Add adv to end of content as much as possible
     * @param   string content: content to add adv
     * @param   array ads: array of advertisment content. One by one will be tried to add to content.
     * @return  string content with adv added.
     * */
    private function add_sms_advertisement($content, $ads)
    {
        if ( ! $ads)
        {
            return $content;
        }
        
        //Get length of adv
        $l1 = 160;
        $l2 = strlen($content);            
        $leng = ($l2==0)?$l1:(($l1+(($l1-$l2)%$l1))%$l1);
        
        $added = array();        
        foreach ($ads as $ad)
        {
            $ad = trim($ad);
            $l1 = strlen($ad);
            if ($l1<$leng)
            {
                $added[] = $ad;
                $leng -= $l1+1;
            }
        }
        $added = implode("\n", $added);
        return $content."\n".$added;
    }
    
         
    /**
     * Get total balance
     * @param   $msisdn: phone number to caculate total balance 
     * @param   $short_code: NULL mean ALL
     * @param   $keyword: MO keyword to count, if not set, 
     * @param   $argument
     *          count all of MO messages belong to this service   
     * */
    protected function get_total_balance($msisdn = NULL,$short_code = NULL,$keyword = NULL,$argument = NULL)
    {
        if ( ! $keyword)
        {
            //Get alias of this service
            $keyword = $this->get_keywords($this->scp->service);
        }
        
        if (! isset($this->MO_Queue))
        {
            $this->load->model('Mo_queue_model','MO_Queue');
        }
        
        return $this->MO_Queue->get_total_balance($msisdn,$short_code,$keyword,$argument);
    }
    
    /**
     * Get all acepted keywords of a service
     * @param string $service: service name, NULL mean curent service
     * @return array 
     */
    public function get_keywords($service = NULL)
    {
        $service = (is_null($service))?$this->scp->service:$service;
        $keywords = $this->Service_model->get_aliases($service);
        $keywords[] = $service;
        
        return $keywords;
    }
    
    public function object_to_array($obj) 
    {        
        if(is_object($obj)) 
        {
            $obj = (array) $obj;
            if (isset($obj['@attributes'])) unset($obj['@attributes']);
        }
        if(is_array($obj)) {
            $new = array();
            foreach($obj as $key => $val) {
                $new[$key] = $this->object_to_array($val);
            }
        }
        else $new = $obj;
    
        return $new;
    }
    
    
    /**
     * Function to send sms
     * @param   array which contains properties, missing params will be replaced by MO information     
     * */
    public function send_sms($sms)
    {        
        $sms = $this->object_to_array($sms);        
        $this->MT->reply_to($this->MO);
        
        foreach($sms as $key=>$value)
        {
            if ($key != 'ads')
            {
                $this->MT->$key = $value;
            }
        }               
        
        if(isset($sms['ads']) && $sms['ads'])
        {            
            $this->MT->content = $this->add_sms_advertisement($this->MT->content, (array)$sms['ads']);
        }
        
        $this->MT->send();
        
    }
    
    /**
     * Function to do wap push
     * @param   object which contain properties, missing params will be replaced by MO information
     * */
    public function wap_push($wap)
    {        
        $wap = $this->object_to_array($wap);
        
        $this->MT->reply_to($this->MO);
        foreach($wap as $key=>$value)
        {
            if ($key != 'ads')
            {
                if ($key == 'link') $key = 'udh';
                $this->MT->$key = $value;
            }
        }               
        
        if(isset($wap['ads']) && $wap['ads'])
        {            
            $this->MT->content = $this->add_sms_advertisement($this->MT->content, (array)$wap['ads']);
        }
        
        $this->MT->wap_push();
        
    }    
  
      
    /**
     *Send http request
     * @param string $url 
     * @return boolean
     */
    public function http_request($url)
    {
        return TRUE;
    }       
    
    /**
     *Raise a system event such as 'after_udf', 'timer', ...
     * @param string $event_name
     * @return boolean 
     */
    public function raise_event($event_name)
    {
        $this->Event->raise($event_name);
        return TRUE;
    }
    
    
    /**
     * Return maximum mt number allowed by telco.
     * This parameter is set in database
     * @param   $MO: NULL mean $this->MO
     * @return  interger
     * */ 
    protected function max_mt_num($MO = NULL)
    {        
        $this->load->model('Smsc_model');
        $MO = (is_null($MO))?$this->MO:$MO;
        
        return $this->Smsc_model->get_max_mt_num($MO->smsc_id,$MO->short_code);
    }
    
    
    
    /**
     * Function which called to process when an event of 'new_mo' occur
     * */
     
    public function __event_new_mo()
    {
        $this->MO = clone $this->ORI_MO;        
        $this->process_mo($this->MO,$this->scp->root_service_action);        
    }
        
    /**
     * Block direct access
     * */
    public function _remap($method)
    {
        write("Direct module access is not allowed!");
    }    
}
/* End of File */