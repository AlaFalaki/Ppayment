<?php 
error_reporting(0);
/* ------------------------------------- XML PARSE ------------------------------------- */
function makeXMLTree($data)
  {
     $ret = array();
     $parser = xml_parser_create();
     xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
     xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,1);
     xml_parse_into_struct($parser,$data,$values,$tags);
     xml_parser_free($parser);
     $hash_stack = array();
     foreach ($values as $key => $val)
     {
        switch ($val['type'])
        {
           case 'open':
              array_push($hash_stack, $val['tag']);
           break;
           case 'close':
              array_pop($hash_stack);
           break;
           case 'complete':
              array_push($hash_stack, $val['tag']);
              // uncomment to see what this function is doing
              //echo("\$ret[" . implode($hash_stack, "][") . "] = '{$val['value']}';\n");
              eval("\$ret[" . implode($hash_stack, "][") . "] = '{$val['value']}';");
              array_pop($hash_stack);
           break;
        }
     }
     return $ret;
  }
  
  
 /* ------------------------------------- CURL POST TO HTTPS --------------------------------- */
function post2https($fields_arr, $url)
{
		
	//url-ify the data for the POST
	foreach($fields_arr as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	$fields_string = substr($fields_string, 0, -1);
	
	//open connection
	$ch = curl_init();
	
	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,count($fields_arr));
	curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	
	
	//execute post
	$res = curl_exec($ch);
	
	//close connection
	curl_close($ch);
	return $res;
}  
