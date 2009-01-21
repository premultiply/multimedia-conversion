<?php

/*
 * klasa odpowiedzialna za wlasciwa konwersje plikow
 */

class Converter {
	
	// funkcja konwertujaca dany plik do danego formatu w danej jakosci
	public function convert($filename, $format, $quality) {
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$formats = $registry->formats;
		$ffmpegPath = $config->path->ffmpeg;
		$destFile = $filename.'.'.$quality.'.'.$format;
		if ($formats->{$format}->mediatype == 'video') {
			$ffmpegObj = new ffmpeg_movie($filename);
			$srcWidth = $this->_makeMultipleTwo($ffmpegObj->getFrameWidth());
			$srcHeight = $this->_makeMultipleTwo($ffmpegObj->getFrameHeight());
			$width = $this->_makeMultipleTwo($formats->{$format}->{$quality}->width);
			if (empty($formats->{$format}->{$quality}->height)) {
				$height = $this->_makeMultipleTwo(round(($srcHeight * $width) / $srcWidth));
			} else {
				$height = $formats->{$format}->{$quality}->height;
			}
			$fps = $ffmpegObj->getFrameRate();
			if ($formats->{$format}->{$quality}->pass->first && $formats->{$format}->{$quality}->pass->second) {
				exec($ffmpegPath . " -i " . $filename . ' ' . $formats->{$format}->{$quality}->pass->first." -r ".$fps." -s ".$width . 'x' . $height . " -y ". $config->path->null);
				exec($ffmpegPath . " -i " . $filename . ' ' . $formats->{$format}->{$quality}->pass->second." -r ".$fps." -s ".$width . 'x' . $height . ' ' . $destFile);
			} elseif ($formats->{$format}->{$quality}->pass->first) {
				exec($ffmpegPath . " -i " . $filename . ' ' . $formats->{$format}->{$quality}->pass->first." -r ".$fps." -s ".$width . 'x' . $height . ' ' . $destFile);
			} else {
				return 'error';
			}
			$convertedMovie = new ffmpeg_movie($destFile);
			$frame = $convertedMovie->getFrame($this->_makeMultipleTwo($convertedMovie->getFrameCount()) / 2);
			imagejpeg($frame->toGDImage(), $filename.'.jpg');
		} elseif ($formats->{$format}->mediatype == 'audio') {
			exec($ffmpegPath . " -i " . $filename . ' ' . $formats->{$format}->{$quality}->pass->first. ' ' . $destFile);
		} else {
			return 'error';
		}
			if (filesize($destFile) > 0) {
		if (file_exists($destFile)) {
				return 'success';
			} else {
				return 'error';
			}
		} else {
			return 'error';
		}	
	}
	
	//zamiana liczby na parzysta (potrzebne dla ffmpeg)
	private function _makeMultipleTwo($value) {
		if($value % 2 == 0) {
			return $value;
		} else {
			return ($value-1);
		}
	}
}