<?php

/*
 * klasa reprezentująca film
 */

class Movie {
	
	private $info = array();
	private $filename = null, $ffmpegPath = null;
	
	
	public function __construct($path) {
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$this->ffmpegPath = $config->path->ffmpeg;
		$this->filename = $path;
		if(!file_exists($path)) {
			throw new Exception('File does not exist');
		}
		exec($this->ffmpegPath . ' -i "' . $path . '" 2>&1', $output);
		$output = implode("\n", $output);
		$regexp = '/^[\d\D\s\n]*Metadata\n'.
			'(?:\s*duration\s*:\s)?(?P<duration>\w*)?\n?'.
			'(?:\s*width\s*:\s)?(?P<width>\w*)?\n?'.
			'(?:\s*height\s*:\s)?(?P<height>\w*)?\n?'.
			'(?:\s*videodatarate\s*:\s)?(?P<videodatarate>\w*)?\n?'.
			'(?:\s*framerate\s*:\s)?(?P<framerate>\w*)?\n?'.
			'(?:\s*videocodecid\s*:\s)?(?P<videocodecid>\w*)?\n?'.
			'(?:\s*audiodatarate\s*:\s)?(?P<audiodatarate>\w*)?\n?'.
			'(?:\s*audiosamplerate\s*:\s)?(?P<audiosamplerate>\w*)?\n?'.
			'(?:\s*audiosamplesize\s*:\s)?(?P<audiosamplesize>\w*)?\n?'.
			'(?:\s*stereo\s*:\s)?(?P<stereo>\w*)?\n?'.
			'(?:\s*audiocodecid\s*:\s)?(?P<audiocodecid>\w*)?\n?'.
			'(?:\s*filesize\s*:\s)?(?P<filesize>\w*)?/';
		echo $regexp;
		preg_match($regexp, $output, $this->info);
		$empty = true;
		echo '<pre>';
		echo $output . "\n";
		var_dump($this->info);
		echo '</pre>';
		foreach ($this->info as $line) if($line) $empty = false;
		if($empty) {
			throw new Exception('Unknown format');
		}
	}
	
	public function getDuration() {
			if ($this->info[1] > 0) return $this->info['duration'];
				 else return 0;
	}
		
	public function getFrameWidth() {
		if ($this->info[2] > 0) return $this->info['width'];
			 else return 0;
	}
	
	public function getFrameHeight() {
		if ($this->info[3] > 0) return $this->info['height'];
			 else return 0;
	}
	
	public function getFrameRate() {
		if ($this->info[5] > 0) return $this->info['framerate'];
			 else return 0;
	}
	
	public function saveFrame($frameNo, $path) {
		exec($this->ffmpegPath . ' -i "' . $this->filename . '" -f image2 -ss ' . $frameNo . ' -an -vframes 1 -y "' . $path . '"');
		if(!file_exists($path) || !filesize($path))	throw new Exception('Unable to save frame');
	}
	
}