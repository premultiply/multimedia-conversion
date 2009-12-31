<?php

/*
 * klasa reprezentująca film
 */

class Movie {
	
	private $info = array();
	private $filename = null;
	private $ffmpegPath = null;
	
	public function __construct($path) {
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$this->ffmpegPath = $config->path->ffmpeg;
		$this->filename = $path;
		exec($this->ffmpegPath . ' -i "' . $this->filename . '" 2>&1', $output);
		$output = implode("\n", $output);
		preg_match('/^[\d\D\s\n]*duration\s*\:\s(.+)\n\s*width\s*:\s(.+)\n\s*height\s*:\s(.+)\n\s*videodatarate\s*:\s(.+)\n\s*framerate\s*:\s(.+)\n\s*videocodecid\s*:\s(.+)\n\s*audiosamplerate\s*:\s(.+)\n\s*audiosamplesize\s*:\s(.+)\n\s*stereo\s*:\s(.+)\n\s*audiocodecid\s*:\s(.+)\n\s*filesize\s*:\s(.+)/',
			$output, $this->info);
		echo '<pre>';
		var_dump($this->info);
		echo '</pre>';
					
	}
	
	public function getDuration() {
			if ($this->info[1] > 0) return $this->info[1];
				 else return 0;
	}
		
	public function getFrameWidth() {
		if ($this->info[2] > 0) return $this->info[2];
			 else return 0;
	}
	
	public function getFrameHeight() {
		if ($this->info[3] > 0) return $this->info[3];
			 else return 0;
	}
	
	public function getFrameRate() {
		if ($this->info[5] > 0) return $this->info[5];
			 else return 0;
	}
	
	public function saveFrame($frameNo, $path) {
		exec($this->ffmpegPath . ' -i "' . $this->filename . '" -f image2 -ss ' . $frameNo . ' -an -vframes 1 -y "' . $path . '"');		
	}
	
}