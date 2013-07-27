<?php
  $fields = array (
    'from' => '7927',
    'to' => '+84996660003',
    'smsc' => 'GSM-Modem',
    'link' => 'www.my-funny-link.com/myfile.mid',
    'title' => 'Hi, look at this'
  );

  $kannel = array (
    'host' => 'mgwsrv.mvas.vn',
    'port' => 8888,
    'user' => 'mvas',
    'pass' => 'Sec@6789ret'
  );
  echo "sending...";
  $result = send_wap_push ( $fields, $kannel );
  print "SENT<br>$result<br>";
  echo "done";


/*
  sendwappush function.
  Copyleft 2004 by Alejandro Guerrieri
  This code is open source and GPL licensed
*/
  function send_wap_push ( $fields, $kannel ) {
    $fields[udh]  = '%06%05%04%0B%84%23%F0';
    $fields[text] = '%1B%06%01%AE%02%05%6A%00%45%C6%0C%03'.
      hex_encode($fields['link'], '%').
      '%00%01%03'.
      hex_encode($fields['title'], '%').
      '%00%01%01';
    unset ( $fields['title'], $fields['link'] ); 

    while(list($k,$v) = each($fields)) {
      if ( $v != "" ) {
        $string .= "&$k=$v";
      }
    }
    $request = 'http://'.$kannel['host'].':'.$kannel[port].
      '/cgi-bin/sendsms'.
      '?user='.$kannel['user'].
      '&pass='.$kannel['pass'].
      $string;
    $result = @file( $request );
    return 'ok';
  }
        
  function hex_encode( $text, $joiner='' ) {
    for ($l=0; $l<strlen($text); $l++) {
      $letter = substr($text, $l, 1);
      $ret .= sprintf("%s%02X", $joiner, ord($letter));
    }
    return $ret;
  }
?>
