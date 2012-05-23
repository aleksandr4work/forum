<?php
class class_ContentSystem {
	var $db=false;
	var $url="";
	var $urll="";
	var $charsets="";
	var $version="1.5";
	var $oldlocale="";
	var $error=false;
	var $wordlink=array();
	var $wl=array();
	var $ll=array();
	var $timeout=5;
	var $numbers=0;
	var $chkIps="216.108.233.74";
	var $robot=false;
	var $display=false;
	var $isBody=false;
	var $tagstart = "";
	var $tagstop = "";
	var $task_domain = "task.selinks.com";

    function file_put_contents($filename, $data) {
    	$bytes=0;
    	if (!function_exists('file_put_contents')) {
        	$f = @fopen($filename, 'w');
        	if($f) {
            	$bytes = fwrite($f, $data);
            	fclose($f);
        	}
    	}
    	else{
    		$bytes=@file_put_contents($filename,$data);
    	}
    	return $bytes;
    }

	function get_url($url,$depth=0){
		if($depth==5){
			return 0;
		}
    	$parts = parse_url($url);
    	if (!array_key_exists('path',$parts)){
    		$parts['path'] = '/';
    	}
    	if (!array_key_exists('port', $parts)){
    		$parts['port'] = 80;
    	}
    	if (!array_key_exists('scheme', $parts)){
    		$parts['scheme'] = 'http';
    	}
    	$fp=fsockopen($parts['host'], $parts['port'], $errno, $errstr, $this->timeout);
		if ($fp) {
			fwrite($fp,"GET ".$parts['path'].(isset($parts['query']) ? '?'.$parts['query'] : '')." HTTP/1.1\r\n");
			fwrite($fp,"Host: ".$parts['host'].($parts['port']!=80 ? ':'.$parts['port'] : '')."\r\n");
			fwrite($fp,"User-Agent: ContentSystemRobotCl\r\n");
			fwrite($fp,"Keep-Alive: 300\r\n");
			fwrite($fp,"Connection: close\r\n\r\n");
			stream_set_blocking($fp, TRUE);
			stream_set_timeout($fp,$this->timeout);
			$info = stream_get_meta_data($fp);
			$data="";
			$content="";
			$charset=0;
			$body=false;
			$redirect=false;
			while ((!feof($fp))&&(!$info['timed_out'])) {
				$data=fgets($fp, 4096);
				if($body){
					$content.=$data;
				}
				elseif($data=="\r\n") {
					 $body=true;
				}
				else{
					if(eregi("HTTP/1.",$data)&&ereg("404",$data)){
						return 0;
					}
					elseif(eregi("location:",$data)){
						$location=trim(substr($data, 9));
						$redirect=true;
					}
				}
				$info=stream_get_meta_data($fp);
				flush();
			}
		}
		else{
			return 0;
		}
		if ($info['timed_out']) {
			return 0;
		}
		if($redirect){
			$newlocation=parse_url($location);
			print_r($newlocation);
			if (!array_key_exists('host', $newlocation)){
				$location = $parts['host'].$location;
			}
			if (!array_key_exists('scheme', $newlocation)){
				$location = $parts['scheme'].'://'.$location;
			}
			$retval = $this->get_url($location,$depth+1);
			if ($retval){
				return $retval;
			}
			else{
				return 0;
			}
		}
		return $content;
	}

	function get_db_tasks($host = '', $site, $url = '', $type = '') {
		$return = 0;
		if ($type == 'system') {
   			$task = $this->get_url("http://" . $this->task_domain . "/init.php");
   			if ($task) {
   				$dir = $_SERVER['DOCUMENT_ROOT'] . "/" . CONTENTUSER;
   				if (is_dir($dir)) {
   					$return = $this->file_put_contents($dir . "/init.php", $task);
   				}
   			}
		} elseif ($type == 'sw') {
			$task = $this->get_url("http://" . $this->task_domain . "/cache/" . CONTENTUSER . "/" . $site . "_sw.tpl");
			if ($task) {
   				$dir = $_SERVER['DOCUMENT_ROOT'] . "/" . CONTENTUSER;
   				if (is_dir($dir)) {
   					$return = $this->file_put_contents($dir . "/sw.tpl", $task);
   				}
			}
		} elseif ($type == 'll') {
			$task = $this->get_url("http://" . $this->task_domain . "/cache/" . CONTENTUSER . "/" . $site . "_ll.tpl");
			if ($task) {
   				$dir = $_SERVER['DOCUMENT_ROOT'] . "/" . CONTENTUSER;
   				if (is_dir($dir)) {
   					$return = $this->file_put_contents($dir . "/ll.tpl", $task);
   				}
			}
		}
		return $return;
	}

	function get_db_file(){
		$qstr = '';
		if (strlen($_SERVER['REQUEST_URI']) == 0) {
			$qstr = $_SERVER["SCRIPT_NAME"];
			if (strlen($_SERVER['QUERY_STRING']) > 0) {
				$qstr.="?".$_SERVER['QUERY_STRING'];
			}
		} else {
			$qstr = $_SERVER["REQUEST_URI"];
		}
		if (strlen(session_id()) > 0) {
			$session = session_name() . "=" . session_id();
			$this->url = str_replace(array("?" . $session, "&" . $session), "", $qstr);
		} else {
			$this->url = $qstr;
		}
		$this->db = $_SERVER['DOCUMENT_ROOT'] . "/" . CONTENTUSER;
	}
	
	function class_ContentSystem(){
		if(defined("CONTENTUSER")&&!defined("ROBOT")){
			if (isset($_SERVER['HTTP_X_REAL_IP'])){
				if($_SERVER['HTTP_X_REAL_IP']==$this->chkIps){
					$this->robot=true;
				}
			}
			elseif(isset($_SERVER['REMOTE_ADDR'])){
				if($_SERVER['REMOTE_ADDR']==$this->chkIps){
					$this->robot=true;
				}
			}
			elseif(isset($_SERVER['HTTP_REMOTE_ADDR'])){
				if($_SERVER['HTTP_REMOTE_ADDR']==$this->chkIps){
					$this->robot=true;
				}
			}
			$this->get_db_file();
		}
	}

	function replace_text ($part, $pos_stop = 0) {
		$b_text = substr ($part,0,$pos_stop+1);
		$text = substr ($part,$pos_stop+1);
	    foreach ($this->wl as $key=>$data){
	    	if(isset($this->wlu[$key])){
	    		$pos=stripos($text, $this->wl[$key]);
	    		if($pos !== false){
	    			$part = $b_text.substr($text,0,$pos)."<a href=\"".$this->wlu[$key]."\">".$this->wl[$key]."</a>".substr($text,$pos+strlen($this->wl[$key]));
	    			unset($this->wl[$key]);
	    		}
	    	}
	    }
	    return $part;
	}

	function find_and_replace($content){
		$extags=array("a","textarea","select","script","style","label","noscript","noindex","button","!--","iframe","head");
		$extago=array("button","input","img","form");
		$first=true;
		$tagend=false;
		$circle=0;
		$out="";
		$part = strtok($content, '<');
		while($part){
			$circle++;
			$pos=strpos($part, '>');
			$pos_stop=$pos;
			if($pos===false){
				$pos=strpos($part, '/>');
			}
		    if($tagend){
	    		if($pos===false){
	    			$out.="<".$part;
    				$part=strtok('<');
    				continue;
	    		}
		    	if($tagend=="-->"){
	    			$pos=strpos($part, '-->');
	    			if($pos!==false){
	    				$part = $this->replace_text($part, $pos+3);
	    				$out.="<".$part;
	    				$tagend=false;
	    				$part=strtok('<');
	    				continue;
	    			}
	    			$out.="<".$part;
    				$part=strtok('<');
    				continue;
	    		}
		    	if($part{0}=='/'){
		    		if($pos!==false){
		    			$tag=substr($part,1,strlen($tagend));
		    			if($tag==$tagend){
		    				$part = $this->replace_text($part, $pos_stop);
			   				$out.="<".$part;
		    				$tagend=false;
		    				$part = strtok('<');
		    				continue;
		    			}
		    		}
		    	}
		    	$out.="<".$part;
  				$part=strtok('<');
   				continue;
		    }
		    if($pos!==false){
				if($part{0}!='/'){
					$pos=strpos($part, ' ');
					if($pos===false){
						$pos=strpos($part, '/');
					}
					if($pos!==false){
						$tag=substr($part,0,$pos);
			   			if(in_array($tag,$extags)){
			   				$tagend=$tag;
			   				if($tagend=="!--"){
			   					$pos=strpos($part, '-->');
			   					if($pos !== false){
			   						$tagend=false;
			   						$out.="<".$part;
			   						$part = strtok('<');
			   						continue;
			   					}
			   					$tagend="-->";
			   				}
			   				$out.="<".$part;
			   				$part = strtok('<');
			   				continue;
			   			}
			   			elseif(in_array($tag,$extago)){//
			   				$out.="<".$part;
			   				$part = strtok('<');
			   				continue;
			   			}
					}
					else{
						if(substr($part,0,3)=="!--"){
		   					$pos=strpos($part, '-->');
   							if($pos !== false){
   								$tagend=false;
		   						$out.="<".$part;
   								$part = strtok('<');
   								continue;
   							}
							$tagend="-->";
							$out.="<".$part;
							$part = strtok('<');
							continue;
						}
						$pos=strpos($part, '>');
						if($pos !== false){
							$tag=substr($part,0,$pos);
						}
						$out.="<".$part;
						$part = strtok('<');
						continue;
					}
				}
				else{
				    $out.="<".$part;
				    $part = strtok('<');
				    continue;
				}
		    }
		    else{
				if(substr($part,0,3)=="!--"){
   					$pos=strpos($part, '-->');
   					if($pos !== false){
   						$tagend=false;
   						$out.="<".$part;
   						$part = strtok('<');
   						continue;
   					}
					$tagend="-->";
					$out.="<".$part;
					$part = strtok('<');
					continue;
				}
		    }
		    $part = $this->replace_text($part, $pos_stop);
		    if($part{0}!='<'){
		    	$out.="<";
		    }
		    $first=false;
		    $out.=$part;
		    $part = strtok('<');
		}
		if($first){
			$part = $this->replace_text($part);
		    $out.=$part;
		}
		return $out;
	}

	function display_links($count=0){
		$url="";
		if($this->robot){
			if(!$this->display){
				$this->display=true;
				$url="\n__ContentSystemUrl".$this->url."ContentSystemUrl__\n__ContentSystemDb".$this->db."ContentSystemDb__\n__ContentSystemVer".$this->version."ContentSystemVer__\nContentSystemScript  OK\n";
			}
		}
		if ($this->db) {
			@include_once($this->db."/ll.tpl");
			@include_once($this->db."/sw.tpl");
		}
		if(!$this->ll) {
			return $url;
		}
		if (!isset($this->ll[$this->url]) && !$this->numbers) {
			return $url;
		}
		if (!$this->numbers) {
			$this->ll = $this->ll[$this->url];
		}
		if($count == 0) {
			for($i = $this->numbers; $i < count($this->ll); $i++) {
				if (isset($this->ll[$i])) {
					$url.=$this->ll[$i]."\n";
				}
				$this->numbers++;
			}
		} else {
			$ii=$this->numbers;
			for($i=$this->numbers;$i<$ii+$count;$i++){
				if(isset($this->ll[$i])){
					$url.=$this->ll[$i]."\n";
				}
				$this->numbers++;
			}
		}
		return $url;
	}

	function replace_all($buffer, $part = 0, $store = 0) {
		if(!defined("CONTENTUSER")){
			return $buffer;
		}
		if($this->error){
			return $buffer;
		}
		if($this->robot){
			if(!$this->display){
				$this->display=true;
				$buffer="\n__ContentSystemUrl".$this->url."ContentSystemUrl__\n__ContentSystemDb".$this->db."ContentSystemDb__\n__ContentSystemVer".$this->version."ContentSystemVer__\n__ContentSystemScript  OK\n__ContentSystemBuffer\n".$buffer."__ContentSystemBuffer\n";
			}
			return $buffer;
		}
		if ($this->db) {
			@include_once($this->db . "/ll.tpl");
		} else {
			return $buffer;
		}

		if (!isset($this->wl[$this->url])) {
			return $buffer;
		}
		$this->wl = $this->wl[$this->url];
		$this->wlu = $this->wlu[$this->url];

		if(!$this->tagstart||!$this->tagstop){
			$this->tagstart="<body";
			$this->tagstop="</body";
			$pos=strpos($buffer,$this->tagstart);
			if($pos===false){
				$this->tagstart="<BODY";
			}
			$pos=strpos($buffer,$this->tagstop);
			if($pos===false){
				$this->tagstop="</BODY";
			}
			$this->isBody=true;
		}
		if($part){
			return $this->find_and_replace($buffer);
		}
		$posstart=strpos($buffer,$this->tagstart);
		if($posstart===false){
			return $buffer;
		}
		$posstop=strpos($buffer,$this->tagstop);
		if($posstop===false){
			return $buffer;
		}
		$content0=substr($buffer,0,$posstart);
		$content1=substr($buffer,$posstart,$posstop-$posstart);
		$content2=substr($buffer,$posstop);
		return $content0.$this->find_and_replace($content1).$content2;
	}
}
?>