<?php

/*
 * klasa odpowiedzialna za wlasciwa konwersje plikow
 */

class Converter {
	
	// funkcja konwertujaca dany plik do danego formatu w danej jakosci
	public function convert($filename, $format, $quality) {
		if (file_exists($filename)) {
			$registry = Zend_Registry::getInstance();
			$config = $registry->configuration;
			$formats = $registry->formats;
			$ffmpegPath = $config->path->ffmpeg;
			$destFile = $filename.'.'.$quality.'.'.$format;
			if ($formats->{$format}->mediatype == 'video') {
				$srcWidth = 0;
				$srcHeight = 0;
				$ffmpegObj = @new ffmpeg_movie($filename);  // zabezpieczenie przed zlymi plikami
				if ($ffmpegObj) {
					$srcWidth = $this->_makeMultipleTwo($ffmpegObj->getFrameWidth());
					$srcHeight = $this->_makeMultipleTwo($ffmpegObj->getFrameHeight());
				}
				if ($srcWidth == 0 || $srcHeight == 0) {
					return 'Error: invalid resource';
				}
				$width = $this->_makeMultipleTwo($formats->{$format}->{$quality}->width);
				if (empty($formats->{$format}->{$quality}->height)) {
					$height = $this->_makeMultipleTwo(round(($srcHeight * $width) / $srcWidth));
				} else {
					$height = $formats->{$format}->{$quality}->height;
				}
				$fps = $ffmpegObj->getFrameRate();
				if ($formats->{$format}->{$quality}->pass->first && $formats->{$format}->{$quality}->pass->second) {
					exec('cd ' . dirname($filename) . " && " . $ffmpegPath . " -i \"" . $filename . '" ' . $formats->{$format}->{$quality}->pass->first." -r ".$fps." -s ".$width . 'x' . $height . " -y ". $config->path->null . " && " . $ffmpegPath . " -i \"" . $filename . '" ' . $formats->{$format}->{$quality}->pass->second." -r ".$fps." -s ".$width . 'x' . $height . ' "'. $destFile .'"');
				} elseif ($formats->{$format}->{$quality}->pass->first) {
					exec($ffmpegPath . " -i \"" . $filename . '" ' . $formats->{$format}->{$quality}->pass->first." -r ".$fps." -s ".$width . 'x' . $height . ' "'. $destFile .'"');
				} else {
					return 'Error: invalid format';
				}
				if ($formats->{$format}->thumbs) {
					$convertedMovie = new ffmpeg_movie($destFile);
					$frame = $convertedMovie->getFrame($this->_makeMultipleTwo($convertedMovie->getFrameCount()) / 2);
					imagejpeg($frame->toGDImage(), $filename.'.jpg');
				}
			} elseif ($formats->{$format}->mediatype == 'audio') {
				exec($ffmpegPath . " -i \"" . $filename . '" ' . $formats->{$format}->{$quality}->pass->first. ' "'. $destFile .'"');
			} else {
				return 'Error: invalid mediatype';
			}
			if (file_exists($destFile)) {
				if (filesize($destFile) > 0) {
					return 'success';
				} else {
					return 'Error: unable to convert this file';
				}
			} else {
				return 'Error: unable to convert this file';
			}
		} else {
			return 'Error: file does not exist in filesystem.';
		}
	}
	
	public function remix($xml, $filename, $format, $quality) {
		if (is_object($xml->movie[0]->attributes())) {
			$i = 0;
			$path = dirname($filename) . '/';
			foreach ($xml->movie as $movie) {
				if (isset($movie->attributes()->url)) {
					$file_temp = 'temp' . $i;
					copy($movie->attributes()->url, $path.$file_temp);
					$time = '';
					$start = 0;
					if (isset($movie->attributes()->start)) {
						$start = $movie->attributes()->start;
						$time .= ' -ss ' . $start;
						
					}
					if (isset($movie->attributes()->end)) {
						$end = $movie->attributes()->end - $start;
						$time .= ' -t ' . $end;
					}
					exec('ffmpeg -i "' . $path . $file_temp . '"' . $time .' -sameq -y "' . $path . $file_temp . '.mpg"');
					if ($i == 0) {
						rename($path . 'temp' . $i . '.mpg', $path . basename($filename) . "_remixed");
					} else {
						copy($path . basename($filename) . '_remixed', $path . 'temp_remixed');
						exec('cat "' . $path . 'temp_remixed" "' . $path . 'temp' . $i . '.mpg" > "' .$path . basename($filename) . '_remixed"');
						unlink($path . 'temp_remixed');
					}
					$i++;
				}
			}
			$result = $this->convert($path . basename($filename) . "_remixed", $format, $quality);
			return $result;
		} else {
			return 'Error: invalid XML';
		}
	}
	
	private function _getParameters($filename) {
		
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