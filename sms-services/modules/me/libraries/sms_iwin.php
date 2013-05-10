<?php

class Sms_iwin {

    var $url = 'http://pay.gomobi.vn/index.php/sms/sms/partner/mvas';

    /**
     *
     * @param string $user
     * @param string $password
     * @param string $smsid
     * @param string $sender
     * @param string $receiver
     * @param string $content
     * @param string $route
     * @param string $timereceived 
     */
    function Call($user, $password, $smsid, $sender, $receiver, $content, $route, $timereceived) {
        $password = md5($smsid . $sender . $receiver . $content . $password);
        $param = '?username=' . urlencode($user) . '&password=' . urlencode($password) . '&smsid=' . urlencode($smsid) . '&sender=' . urlencode($sender) . '&receiver=' . urlencode($receiver) . '&content=' . urlencode($content) . '&route=' . urlencode($route) . '&timereceived=' . urlencode($timereceived);
        $result = file_get_contents($this->url . $param);           
        if ($result) {
            $result = explode('|',$result);
            $return['type'] = array_shift($result);
            $return['title'] = array_shift($result);
            $return['CDR'] = array_shift($result);
            $return['content'] = implode('|', $result);            
            return $return;
        } else {
            return FALSE;
        }
    }

}

