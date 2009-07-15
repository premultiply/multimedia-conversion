<?php

/*
 * ten kontroler sprawdza czy nie ma plików do konwersji lub usunięcia, jeśli są to podejmuje odpowiednie działania
 */

class CronController extends Zend_Controller_Action {
	
	public function indexAction(){
		// potrzebne zmienne
		$config = Zend_Registry::getInstance()->configuration;
		$jobsTable = new Jobs();
		$converter = new Converter();
		$status = new Status();
		
		// usuwanie zadań zakończonych dalej niż X godzin temu (x określone w config.ini)
		$where = array(
			'uploaded IS NOT NULL',
			'deleted IS NULL',
			'converted IS NOT NULL' ,
			'conversion_started IS NOT NULL'
		);
		$jobs = $jobsTable->fetchAll($where, null, 3);
		
		foreach($jobs as $job) {
			$time = time() - strtotime(preg_replace("/\..*/","",$job->converted));
			$lifetime =  3600 * $config->file->lifetime;
			if ($time > $lifetime) {
				$this->_delete($config->path->files.$job->id);
				$job->deleted = 'now';
				$job->deletion_reason = 'outdated';
				$job->save();
			}
		}
		
		
		// konwersja wszystkich wgranych i nieskonwertowanych plików
		$jobs = $jobsTable->fetchAll(array(
			'uploaded IS NOT NULL',
			'deleted IS NULL',
			'converted IS NULL' ,
			'conversion_started IS NULL'
		));
		
		foreach($jobs as $job) { // zaznaczenie rozpoczetych zadan aby w przypadku odpalenia tego kontrolera zanim poprzednia instancja zakonczyla prace te same zadania nie byly wykonywane wiecej niz jeden raz
			$job->conversion_started = 'now';
			$job->save();
		}
		
		
		// konwersja
		foreach($jobs as $job) {
			$filename = $config->path->files.$job->id . '/' . $job->filename;
			@$xml = simplexml_load_file($filename);
			if (is_object($xml)) {
				$result = $converter->remix($xml, $filename, $job->format, $job->quality);
			} else {
				$result = $converter->convert($filename, $job->format, $job->quality);
			}
			if ($result == 'success'){
				$job->converted = 'now';
				$job->save();
				if ($job->status_url) {
					$status->respond($job->status_url, 'OK');
				}
			} else {
				$this->_delete($config->path->files.$job->id);
				$job->deleted = 'now';
				$job->deletion_reason = $result;
				$job->save();
				if ($job->status_url) {
					$status->respond($job->status_url, $result);
				}
			}	
		}
	}
	
	// usuwanie katalogu wraz z zawartością
	
	private function _delete($path) {
    	$path= rtrim($path, '/').'/';
    	$handle = opendir($path);
    	for (;false !== ($file = readdir($handle));)
        	if($file != "." and $file != ".." ) {
            	$fullpath= $path.$file;
            	if( is_dir($fullpath) ) {
                	$this->_delete($fullpath);
                	rmdir($fullpath);
            	} else
            		unlink($fullpath);
        }
    	closedir($handle);
    	rmdir($path);
	}
}