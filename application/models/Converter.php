<?php

/*
 * klasa odpowiedzialna za wlasciwa konwersje plikow
 */

class Converter {

	private $i, $d = 0;

	// funkcja konwertujaca dany plik do danego formatu w danej jakosci
	public function convert($filename, $format, $quality) {
		if (file_exists($filename)) {
			$registry = Zend_Registry::getInstance();
			$config = $registry->configuration;
			$formats = $registry->formats;
			$ffmpegPath = $config->path->ffmpeg;
			$flvtool2Path = $config->path->flvtool2;
			$destFile = $filename.'.'.$quality.'.'.$format;
			if ($formats->{$format}->mediatype == 'video') {
				$srcWidth = 0;
				$srcHeight = 0;
				if($config->ffmpeg-php) ffmpeg_movie($filename);
				else $ffmpegObj = new Movie($filename);
				if ($ffmpegObj) {
					$srcWidth = $this->_makeMultipleTwo($ffmpegObj->getFrameWidth());
					$srcHeight = $this->_makeMultipleTwo($ffmpegObj->getFrameHeight());
					$fps = $ffmpegObj->getFrameRate();
					unset($ffmpegObj);
				}
				if ($srcWidth == 0 || $srcHeight == 0) {
					throw new Exception('Error: invalid resource');
				}
				$width = $this->_makeMultipleTwo($formats->{$format}->{$quality}->width);
				if (empty($formats->{$format}->{$quality}->height)) {
					$height = $this->_makeMultipleTwo(round(($srcHeight * $width) / $srcWidth));
				} else {
					$height = $formats->{$format}->{$quality}->height;
				}
				if ($formats->{$format}->{$quality}->pass->first->ffmpeg && $formats->{$format}->{$quality}->pass->second->ffmpeg) {
					exec('cd ' . dirname($filename) . ' && ' . $ffmpegPath . ' -i "' . $filename . '" ' . $formats->{$format}->{$quality}->pass->first->ffmpeg.' -r '.$fps." -s ".$width . 'x' . $height . " -y ". $config->path->null . ' && ' . $ffmpegPath . ' -i "' . $filename . '" ' . $formats->{$format}->{$quality}->pass->second->ffmpeg.' -r '.$fps.' -s '.$width . 'x' . $height . ' "'. $destFile .'"');
				} elseif ($formats->{$format}->{$quality}->pass->first->ffmpeg) {
					echo($ffmpegPath . ' -i "' . $filename . '" ' . $formats->{$format}->{$quality}->pass->first->ffmpeg.' -r '.$fps.' -s '.$width . 'x' . $height . ' "'. $destFile .'"');
				} else {
					throw new Exception('Error: invalid format');
				}
				if ($formats->{$format}->thumbs && file_exists($destFile) && filesize($destFile) > 0) {
					if($config->ffmpeg-php) {
						$convertedMovie = new ffmpeg_movie($destFile);
						$frameNo = $this->_makeMultipleTwo($convertedMovie->getFrameCount()) / 2;
						$frameNo = $frameNo > 0 ? $frameNo : 1;
						$frame = $convertedMovie->getFrame($frameNo);
						imagejpeg($frame->toGDImage(), $filename.'.jpg');
					} else {
						$convertedMovie = new Movie($destFile);
						$time = $this->_makeMultipleTwo($convertedMovie->getDuration()) / 2;
						$time = $time > 0 ? $time : 1;
						$convertedMovie->saveFrame($time, $filename.'.jpg');
					}
					unset($convertedMovie);
				}
				if ($formats->{$format}->qtf) {
					$this->_makeStreamableWithQtf($filename . '.' . $quality . '.' . $format);
				}
				if ($formats->{$format}->flvtool2) {
					$this->_makeStreamableWithFlvtool2($filename . '.' . $quality . '.' . $format);
				}
			} elseif ($formats->{$format}->mediatype == 'audio') {
				exec($ffmpegPath . " -i \"" . $filename . '" ' . $formats->{$format}->{$quality}->pass->first->ffmpeg. ' "'. $destFile .'"');
			} else {
				throw new Exception('Error: invalid mediatype');
			}
			if (file_exists($destFile) && filesize($destFile) > 0) {
				return 'success';
			} else {
				throw new Exception('Error: unable to convert this file');
			}
		} else {
			throw new Exception('Error: file does not exist in filesystem.');
		}
	}

	/*public function remix_old($xml, $filename, $format, $quality) {
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
		}*/

	/*
	 * ta funkcja zajmuje sie xmlami wrzuconymi do mc
	 *
	 * INEGRACJA Z MLT
	 */
	public function remix($xml, $filename, $format, $quality) {
		$path = dirname($filename) . '/';
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$formats = $registry->formats;
		$inigoPath = $config->path->inigo;
		$flvtool2Path = $config->path->flvtool2;
		$xml = $this->_convertXML($xml, $path, $config->xml->depth);
		/*echo "<pre>";
		 print_r($xml);			//przydatne przy rozwiazywaniu problemow
		 echo "</pre>";*/
		file_put_contents($filename . '.westley', $xml->asXML());
		if ($formats->{$format}->{$quality}->pass->second->mlt) {
			exec('cd ' . $path . ' && ' . $inigoPath . ' "' . $filename . '.westley" -consumer avformat:"' . $filename . '.' . $quality . '.' . $format .'" ' . $formats->{$format}->{$quality}->pass->first->mlt . ' && ' . $inigoPath . ' "' . $filename . '.westley" -consumer avformat:"' . $filename . '.' . $quality . '.' . $format .'" ' . $formats->{$format}->{$quality}->pass->second->mlt);
		}
		elseif ($formats->{$format}->{$quality}->pass->first->mlt) {
			exec('cd ' . $path . ' && ' . $inigoPath . ' "' . $filename . '.westley" -consumer avformat:"' . $filename . '.' . $quality . '.' . $format .'" ' . $formats->{$format}->{$quality}->pass->first->mlt);
		}
		if ($formats->{$format}->thumbs && file_exists($filename . '.' . $quality . '.' . $format) && filesize($filename . '.' . $quality . '.' . $format) > 0) {
			if($config->ffmpeg-php) {
				$convertedMovie = new ffmpeg_movie($destFile);
				$frameNo = $this->_makeMultipleTwo($convertedMovie->getFrameCount()) / 2;
				$frameNo = $frameNo > 0 ? $frameNo : 1;
				$frame = $convertedMovie->getFrame($frameNo);
				imagejpeg($frame->toGDImage(), $filename.'.jpg');
			} else {
				$convertedMovie = new Movie($filename . '.' . $quality . '.' . $format);
				$time = $this->_makeMultipleTwo($convertedMovie->getFrameCount()) / 2;
				$time = $time > 0 ? $time : 1;
				$convertedMovie->saveFrame($time, $filename.'.jpg');
			}
			unset($convertedMovie);
		}
		if ($formats->{$format}->qtf) {
			$this->_makeStreamableWithQtf($filename . '.' . $quality . '.' . $format);
		}
		if ($formats->{$format}->flvtool2) {
			$this->_makeStreamableWithFlvtool2($filename . '.' . $quality . '.' . $format);
		}
		if (file_exists($filename . '.' . $quality . '.' . $format) && filesize($filename . '.' . $quality . '.' . $format) > 0)
		return 'success';
		else
		throw new Exception('Error: unable to convert this file');
	}
	/*
	 private function _getParameters($filename) {

	 }
	 */
	//zamiana liczby na parzysta (potrzebne dla ffmpeg)
	private function _makeMultipleTwo($value) {
		if(is_numeric($value)) {
			if($value % 2 == 0) {
				return $value;
			} else {
				return ($value - 1);
			}
		} else
		throw new Exception('Error: Given value is not numeric');
	}

	/*
	 *	bardzo sprytna funkcja zajmujaca sie plikami .westley
	 *
	 * 	INTEGRACJA Z MLT
	 */
	private function _convertXML($xml, $path, $maxDepth, $depth = 0) {
		$this->d = $this->d + 1;
		if ($this->d == 1 && $xml->attributes()->root) $xml->attributes()->root = $path; elseif ($this->d == 1) $xml->addAttribute('root', $path);
		foreach ($xml->children() as $property) {
			$start = 0;
			if ($property->attributes()->start) {
				$start = $property->attributes()->start;
				if ($property->attributes()->in) {
					$property->attributes()->in = round($start * 0.025);
				} else {
					$property->addAttribute('in', round($start * 0.025));
				}
			}
			if ($property->getName() != 'blank' && $property->attributes()->len) {
				if ($property->attributes()->out) {
					$property->attributes()->out = round(($property->attributes()->len + $start) * 0.025);
				} else {
					$property->addAttribute('out', round(($property->attributes()->len + $start) * 0.025));
				}
			}
			if ($property->attributes()->stop) {
				$stop = $property->attributes()->stop;
				if ($property->attributes()->out) {
					$property->attributes()->out = round($stop * 0.025);
				} else {
					$property->addAttribute('out', round($stop * 0.025));
				}
			}
			if ($property->getName() == 'blank' && $property->attributes()->len) {
				$len = $property->attributes()->len;
				if ($property->attributes()->length) {
					$property->attributes()->length = round($len * 0.025);
				} else {
					$property->addAttribute('length', round($len * 0.025));
				}
			}
			if ($property->attributes()->name == "resource" && Zend_Uri::check($property)) {
				copy($property, $path."resource".$this->i);
				$property[0] = "resource".$this->i;
				$this->i = $this->i + 1;
				$xmlTemp = @simplexml_load_file($path.$property[0]);
				if (is_object($xmlTemp) && $depth < $maxDepth) $this->_convertXML($xmlTemp, $path, $maxDepth, $depth + 1);
			}
			if($property->children()){
				$property = $this->_convertXML($property, $path, $maxDepth, $depth);
			}
		}
		$this->d = $this->d - 1;
		return $xml;
	}

	private function _makeStreamableWithQtf($filename) {
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$qtfPath = $config->path->qtf;
		exec($qtfPath . ' "' . $filename . '" "' . $filename . '.qtf"');
		unlink($filename);
		rename($filename . '.qtf', $filename);
	}

	private function _makeStreamableWithFlvtool2($filename) {
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$flvtool2Path = $config->path->flvtool2;
		exec($flvtool2Path . ' -UP "' . $filename . '"');
	}
}