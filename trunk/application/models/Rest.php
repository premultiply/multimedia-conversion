<?php

/*
 * klasa odpowiedzialna za obsluge zapytan
 */

class Rest {
	
	//wgrywanie na serwer, zapytania http POST lub GET
	public function upload($format, $quality = 'normal', $url = null, $statusUrl = null) {
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$formats = $registry->formats;
		$key = $this->_getRandomStr();
		$upDir = $config->path->files.'/'.$key.'/';
		mkdir($upDir,0777);
		$jobs = new Jobs();
		$data = array(
			'id' => $key,
			'format' => $format,
			'quality' => $quality,
			'upload_started' => 'now',
			'status_url' => $statusUrl
		);
		$jobs->insert($data);
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'POST': 
				$upload = new Zend_File_Transfer_Adapter_Http();
				$upload->setDestination($upDir);
				$upload->addValidator('Count', false, 1);
				if (!$upload->receive()) {
    				$messages = $upload->getMessages();
    				return implode("\n", $messages);
				} elseif(!isset($formats->{$format}->{$quality}))  {
				$xml ='<?xml version="1.0" encoding="UTF-8"?>
					<error>Sorry, this media format or quality are not supported</error>';
   				$xml = simplexml_load_string($xml);
    			return $xml;
				}
				if ($upload->isUploaded()) {
					$filename = $this->_stripExt($upload->getFileName(null, false));
					$filenameFull = $upload->getFileName();
					$filenameFullStr = $this->_stripExt($filenameFull);
					if ($filenameFull != $filenameFullStr) {
						rename($filenameFull, $filenameFullStr);
					}
					$uploaded = true;
				}
				break;
			case 'GET':
				if ($url == null) {
					$xml ='<?xml version="1.0" encoding="UTF-8"?>
						<error>Sorry, you have to specify URL of the file you want to upload</error>';
   					$xml = simplexml_load_string($xml);
					return $xml;
				}
				$filename = $this->_stripExt(basename($url));
				copy($url, $upDir.$filename);
				if (file_exists($upDir.$filename) && filesize($upDir.$filename) > 0) {
					$uploaded = true;
				}
		}
		if ($uploaded) {
			$job = $jobs->fetchRow(array('id = ?' => $key));
			$job->uploaded = 'now';
			$job->filename = $filename;
			$job->save();
			$xml ='<?xml version="1.0" encoding="UTF-8"?>
			<jobId>'.$key.'</jobId>';
   			$xml = simplexml_load_string($xml);
    		return $xml;
		} else {
			$xml ='<?xml version="1.0" encoding="UTF-8"?>
			<error>An error occured when uploading a file</error>';
   			$xml = simplexml_load_string($xml);
    		return $xml;
		}
	}
	
	
	//sprawdzanie statusu pracy
	public function check($jobId) {
		$config = Zend_Registry::getInstance()->configuration;
		$jobs = new Jobs();
		$job = $jobs->fetchRow(array('id = ?' => $jobId));
		if ($job->deleted) {
			$state = 'deleted';
		} elseif ($job->converted) {
			$state = 'converted';
		} elseif ($job->uploaded) {
			$state = 'uploaded';
		} else {
			$state = 'error';
		}
		$xml ='<?xml version="1.0" encoding="UTF-8"?>
			<state>'.$state.'</state>';
		$xml = simplexml_load_string($xml);
    	return $xml;
	}
	
	//zwraca skonwertowany plik
	public function get($jobId) {
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$formats = $registry->formats;
		$jobs = new Jobs();
		$job = $jobs->fetchRow(array('id = ?' => $jobId));
		if ($job->converted && $job->uploaded){
			if (file_exists($config->path->files.$jobId.'/'.$job->filename.'.'.$job->quality.'.'.$job->format)) {
				$filepath = $config->path->files.$jobId.'/'.$job->filename.'.'.$job->quality.'.'.$job->format;
			} elseif (file_exists($config->path->files.$jobId.'/'.$job->filename.'_remixed.'.$job->quality.'.'.$job->format)) {
				$filepath = $config->path->files.$jobId.'/'.$job->filename.'_remixed.'.$job->quality.'.'.$job->format;
			}
			http_send_content_disposition($job->filename.".".$formats->{$job->format}->extension, true);
			http_send_content_type("binary/octet-stream");	
			http_throttle(0.1, $config->http->throttle);
			http_send_file($filepath);
			$job->downloaded = 'now';
			$job->save();
			return;
		} else {
			$xml ='<?xml version="1.0" encoding="UTF-8"?>
			<error>Sorry, this file can\'t be downloaded</error>';
		}
		$xml = simplexml_load_string($xml);
    	return $xml;
	}
	
	
	//zwraca miniaturke skonwertowanego pliku wideo
	public function thumb($jobId) {
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$formats = $registry->formats;
		$jobs = new Jobs();
		$job = $jobs->fetchRow(array('id = ?' => $jobId));
		if (file_exists($config->path->files.$jobId.'/'.$job->filename.'.jpg')) {
			$filepath = $config->path->files.$jobId.'/'.$job->filename.'.jpg';
		} elseif (file_exists($config->path->files.$jobId.'/'.$job->filename.'_remixed.jpg')) {
			$filepath = $config->path->files.$jobId.'/'.$job->filename.'_remixed.jpg';
		}
		if ($job->converted && $job->uploaded && $formats->{$job->format}->thumbs){
			http_send_content_disposition($job->filename.".jpg");
			http_send_content_type("binary/octet-stream");	
			http_throttle(0.1, $config->http->throttle);
			http_send_file($filepath);
			return;
		} else {
			$xml ='<?xml version="1.0" encoding="UTF-8"?>
			<error>Sorry, this file can\'t be downloaded</error>';
		}
		$xml = simplexml_load_string($xml);
    	return $xml;
	}
	
	//generuje losowy, 42 znakowy identyfikator pracy
	private function _getRandomStr() {
		for ($s = '', $i = 0, $z = strlen($a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')-1; $i != 42; $x = rand(0,$z), $s .= $a{$x}, $i++);
		return $s;
	}
	
	//usuwa rozszerzenie z nazwy pliku
	private function _stripExt($filename) {
		$ext = strrchr($filename, '.');
  	    if($ext !== false)
		{
			$filename = substr($filename, 0, -strlen($ext));
		}
		return $filename;
	}
	
	/*public function test() {
		$xml = simplexml_load_file("http://file/test.westley");
		echo '<pre>';
		foreach ($xml->producer as $producer) {
			foreach ($producer->property as $property) {
				if ($property->attributes()->name == "resource") echo $producer->attributes()->id .': ' . $property . "\n";
			}
		}
		echo '</pre>';
		//return $xml;
	}*/
}
