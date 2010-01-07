<?php
/*
 * klasa reprezentuj&#261;ca film
 */

class Movie
{
	private $info = array();
	private $filename = null, $ffmpegPath = null;


	public function __construct($path)
	{
		$registry = Zend_Registry::getInstance();
		$config = $registry->configuration;
		$this->ffmpegPath = $config->path->ffmpeg;
		$this->filename = $path;
		if (!file_exists($path)) {
			throw new Exception('File does not exist');
		}
		/*
		 * this part of code is borrowed from "phpvideotoolkit" distributed under BSD license
		 *
		 * http://code.google.com/p/phpvideotoolkit/
		 *
		 * Copyright (c) by buggedcom and coenen.rob, 2009
		 */
		exec($this->ffmpegPath . ' -i "' . $path . '" 2>&1', $buffer);
		$buffer = implode("\r\n", $buffer);
		$data = array();
		// grab the duration and bitrate data
		preg_match_all('/Duration: (.*)/', $buffer, $matches);
		if (count($matches) > 0) {
			$line = trim($matches[0][0]);
			// capture any data
			preg_match_all('/(Duration|start|bitrate): ([^,]*)/', $line, $matches);

			// setup the default data
			$data['duration'] = array('timecode' => array('seconds' => array('exact' => -1, 'excess' => -1), 'rounded' => -1, ));
			// get the data
			foreach ($matches[1] as $key => $detail) {
				$value = $matches[2][$key];
				switch (strtolower($detail)) {
					case 'duration':
						// print_r($value);
						$data['duration']['timecode']['rounded'] = substr($value, 0, 8);
						$data['duration']['timecode']['frames'] = array();
						$data['duration']['timecode']['frames']['exact'] = $value;
						$data['duration']['timecode']['frames']['excess'] = intval(substr($value, 9));
						break;
					case 'bitrate':
						$data['bitrate'] = strtoupper($value) === 'N/A' ? -1 : intval($value);
						break;
					case 'start':
						$data['duration']['start'] = $value;
						break;
				}
			}
		}

		// match the video stream info
		preg_match('/Stream(.*): Video: (.*)/', $buffer, $matches);
		if (count($matches) > 0) {
			$data['video'] = array();
			// get the dimension parts
			preg_match('/([0-9]{1,5})x([0-9]{1,5})/', $matches[2], $dimensions_matches);
			//print_r($dimensions_matches);
			$dimensions_value = $dimensions_matches[0];
			$data['video']['dimensions'] = array('width' => floatval($dimensions_matches[1]), 'height' => floatval($dimensions_matches[2]));
			// get the framerate
			preg_match('/([0-9\.]+) (fps|tb)/', $matches[0], $fps_matches);
			$data['duration']['timecode']['frames']['frame_rate'] = $data['video']['frame_rate'] = floatval($fps_matches[1]);
			$data['duration']['timecode']['seconds']['total'] = $data['duration']['seconds'] = $this->formatTimecode($data['duration']['timecode']['frames']['exact'], '%hh:%mm:%ss.%fn', '%st.%ms', $data['video']['frame_rate']);
			$fps_value = $fps_matches[0];
			// get the ratios
			preg_match('/\[PAR ([0-9\:\.]+) DAR ([0-9\:\.]+)\]/', $matches[0], $ratio_matches);
			if (count($ratio_matches)) {
				$data['video']['pixel_aspect_ratio'] = $ratio_matches[1];
				$data['video']['display_aspect_ratio'] = $ratio_matches[2];
			}
			// work out the number of frames
			if (isset($data['duration']) && isset($data['video'])) {
				// set the total frame count for the video
				$data['video']['frame_count'] = ceil($data['duration']['seconds'] * $data['video']['frame_rate']);
				// set the framecode
				$data['duration']['timecode']['seconds']['excess'] = floatval($data['duration']['seconds']) - floor($data['duration']['seconds']);
				$data['duration']['timecode']['seconds']['exact'] = $this->formatSeconds($data['duration']['seconds'], '%hh:%mm:%ss.%ms');
				$data['duration']['timecode']['frames']['total'] = $data['video']['frame_count'];
			}
			// formats should be anything left over, let me know if anything else exists
			$parts = explode(',', $matches[2]);
			$other_parts = array($dimensions_value, $fps_value);
			$formats = array();
			foreach ($parts as $key => $part) {
				$part = trim($part);
				if (!in_array($part, $other_parts)) {
					array_push($formats, $part);
				}
			}
			$data['video']['pixel_format'] = $formats[1];
			$data['video']['codec'] = $formats[0];
		}

		// match the audio stream info
		preg_match('/Stream(.*): Audio: (.*)/', $buffer, $matches);
		if (count($matches) > 0) {
			// setup audio values
			$data['audio'] = array('stereo' => -1, 'sample_rate' => -1, 'sample_rate' => -1);
			$other_parts = array();
			// get the stereo value
			preg_match('/(stereo|mono)/i', $matches[0], $stereo_matches);
			if (count($stereo_matches)) {
				$data['audio']['stereo'] = $stereo_matches[0];
				array_push($other_parts, $stereo_matches[0]);
			}
			// get the sample_rate
			preg_match('/([0-9]{3,6}) Hz/', $matches[0], $sample_matches);
			if (count($sample_matches)) {
				$data['audio']['sample_rate'] = count($sample_matches) ? floatval($sample_matches[1]) : -1;
				array_push($other_parts, $sample_matches[0]);
			}
			// get the bit rate
			preg_match('/([0-9]{1,3}) kb\/s/', $matches[0], $bitrate_matches);
			if (count($bitrate_matches)) {
				$data['audio']['bitrate'] = count($bitrate_matches) ? floatval($bitrate_matches[1]) : -1;
				array_push($other_parts, $bitrate_matches[0]);
			}
			// formats should be anything left over, let me know if anything else exists
			$parts = explode(',', $matches[2]);
			$formats = array();
			foreach ($parts as $key => $part) {
				$part = trim($part);
				if (!in_array($part, $other_parts)) {
					array_push($formats, $part);
				}
			}
			$data['audio']['codec'] = $formats[0];
			// if no video is set then no audio frame rate is set
			if ($data['duration']['timecode']['seconds']['exact'] === -1) {
				$exact_timecode = $this->formatTimecode($data['duration']['timecode']['frames']['exact'], '%hh:%mm:%ss.%fn', '%hh:%mm:%ss.%ms', 1000);
				$data['duration']['timecode']['seconds'] = array('exact' => $exact_timecode, 'excess' => intval(substr($exact_timecode, 8)), 'total' => $this->formatTimecode($data['duration']['timecode']['frames']['exact'], '%hh:%mm:%ss.%fn', '%ss.%ms', 1000));
				$data['duration']['timecode']['frames']['frame_rate'] = 1000;
				$data['duration']['seconds'] = $data['duration']['timecode']['seconds']['total'];
				//$this->formatTimecode($data['duration']['timecode']['frames']['exact'], '%hh:%mm:%ss.%fn', '%st.%ms', $data['video']['frame_rate']);
			}
		}


		// check that some data has been obtained
		if (!count($data)) {
			$data = false;
		} else {
			$data['_raw_info'] = $buffer;
		}
		/*
		 * end of code borrowed from phpvideotoolkits
		 * mine miserable ffmpeg output parsing attempt
		 *
		 * $regexp = '/^[\d\D\s\n]*Metadata\n'.
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
		 preg_match($regexp, $output, $this->info);
		 $empty = true;
		*/
		$this->info = $data;
		if (!$data) {
			throw new Exception('Unknown format');
		}
	}

	public function getDuration()
	{
		return $this->info['duration']['seconds'];
	}

	public function getFrameWidth()
	{
		return $this->info['video']['dimensions']['width'];
	}

	public function getFrameHeight()
	{
		return $this->info['video']['dimensions']['height'];
	}

	public function getFrameRate()
	{
		return $this->info['video']['frame_rate'];
	}

	public function getFrameCount()
	{
		return $this->info['video']['frame_count'];
	}

	public function saveFrame($time, $path)
	{
		exec($this->ffmpegPath . ' -i "' . $this->filename . '" -f image2 -ss ' . $time . ' -an -vframes 1 -y "' . $path . '"');
		if (!file_exists($path) || !filesize($path))
		throw new Exception('Unable to save frame');
	}


	/*
	 * this part of code is borrowed from "phpvideotoolkit" distributed under BSD license
	 *
	 * http://code.google.com/p/phpvideotoolkit/
	 *
	 * Copyright (c) by buggedcom and coenen.rob, 2009
	 */
	public function formatTimecode($input_timecode, $input_format = '%hh:%mm:%ss', $return_format = '%ts', $frames_per_second = false, $use_smart_values = true)
	{
		//       first we must get the timecode into the current seconds
		$input_quoted = preg_quote($input_format);
		$placeholders = array('%hh', '%mm', '%ss', '%fn', '%ms', '%ft', '%st', '%sf', '%sc', '%mt');
		$seconds = 0;
		$input_regex = str_replace($placeholders, '([0-9]+)', preg_quote($input_format));
		preg_match('/' . $input_regex . '/', $input_timecode, $matches);
		//       work out the sort order for the placeholders
		$sort_table = array();
		foreach ($placeholders as $key => $placeholder) {
			if (($pos = strpos($input_format, $placeholder)) !== false) {
				$sort_table[$pos] = $placeholder;
			}
		}
		ksort($sort_table);
		//       check to see if frame related values are in the input
		$has_frames = strpos($input_format, '%fn') !== false;
		$has_total_frames = strpos($input_format, '%ft') !== false;
		if ($has_frames || $has_total_frames) {
			//         if the fps is false then we must automagically detect it from the input file
			if ($frames_per_second === false) {
				$info = $this->getFileInfo();
				//           check the information has been received
				if ($info === false || (!isset($info['duration']) || !isset($info['duration']['timecode']['frames']['frame_rate']))) {
					//             fps cannot be reached so return -1
					return - 1;
				}
				$frames_per_second = $info['duration']['timecode']['frames']['frame_rate'];
			}
		}
		//       increment the seconds with each placeholder value
		$key = 1;
		foreach ($sort_table as $placeholder) {
			if (!isset($matches[$key])) {
				break;
			}
			$value = $matches[$key];
			switch ($placeholder) {
				//           time related ones
				case '%hh':
					$seconds += $value * 3600;
					break;
				case '%mm':
					$seconds += $value * 60;
					break;
				case '%ss':
				case '%sf':
				case '%sc':
					$seconds += $value;
					break;
				case '%ms':
					$seconds += floatval('0.' . $value);
					break;
				case '%st':
				case '%mt':
					$seconds = $value;
					break 1;
					break;
					//           frame related ones
				case '%fn':
					$seconds += $value / $frames_per_second;
					break;
				case '%ft':
					$seconds = $value / $frames_per_second;
					break 1;
					break;
			}
			$key += 1;
		}
		//       then we just format the seconds
		return $this->formatSeconds($seconds, $return_format, $frames_per_second, $use_smart_values);
	}


	public function formatSeconds($input_seconds, $return_format = '%hh:%mm:%ss', $frames_per_second = false, $use_smart_values = true)
	{
		$timestamp = mktime(0, 0, $input_seconds, 0, 0);
		$floored = floor($input_seconds);
		$hours = $input_seconds > 3600 ? floor($input_seconds / 3600) : 0;
		$mins = date('i', $timestamp);
		$searches = array();
		$replacements = array();
		//       these ones are the simple replacements
		//       replace the hours
		$using_hours = strpos($return_format, '%hh') !== false;
		if ($using_hours) {
			array_push($searches, '%hh');
			array_push($replacements, $hours);
		}

		//       replace the minutes
		$using_mins = strpos($return_format, '%mm') !== false;
		if ($using_mins) {
			array_push($searches, '%mm');
			//         check if hours are being used, if not and hours are required enable smart minutes
			if ($use_smart_values === true && !$using_hours && $hours > 0) {
				$value = ($hours * 60) + $mins;
			} else {
				$value = $mins;
			}
			array_push($replacements, $value);
		}

		//       replace the seconds
		if (strpos($return_format, '%ss') !== false) {
			//         check if hours are being used, if not and hours are required enable smart minutes
			if ($use_smart_values === true && !$using_mins && !$using_hours && $hours > 0) {
				$mins = ($hours * 60) + $mins;
			}
			//         check if mins are being used, if not and hours are required enable smart minutes
			if ($use_smart_values === true && !$using_mins && $mins > 0) {
				$value = ($mins * 60) + date('s', $timestamp);
			} else {
				$value = date('s', $timestamp);
			}
			array_push($searches, '%ss');
			array_push($replacements, $value);
		}
		//       replace the milliseconds
		if (strpos($return_format, '%ms') !== false) {
			$milli = round($input_seconds - $floored, 3);
			$milli = substr($milli, 2);
			$milli = empty($milli) ? '0' : $milli;
			array_push($searches, '%ms');
			array_push($replacements, $milli);
		}
		//       replace the total seconds (rounded)
		if (strpos($return_format, '%st') !== false) {
			array_push($searches, '%st');
			array_push($replacements, round($input_seconds));
		}
		//       replace the total seconds (floored)
		if (strpos($return_format, '%sf') !== false) {
			array_push($searches, '%sf');
			array_push($replacements, floor($input_seconds));
		}
		//       replace the total seconds (ceiled)
		if (strpos($return_format, '%sc') !== false) {
			array_push($searches, '%sc');
			array_push($replacements, ceil($input_seconds));
		}
		//       replace the total seconds
		if (strpos($return_format, '%mt') !== false) {
			array_push($searches, '%mt');
			array_push($replacements, round($input_seconds, 3));
		}
		//       these are the more complicated as they depend on $frames_per_second / frames per second of the current input
		$has_frames = strpos($return_format, '%fn') !== false;
		$has_total_frames = strpos($return_format, '%ft') !== false;
		if ($has_frames || $has_total_frames) {
			//         if the fps is false then we must automagically detect it from the input file
			if ($frames_per_second === false) {
				$info = $this->getFileInfo();
				//           check the information has been received
				if ($info === false || (!isset($info['video']) || !isset($info['video']['frame_rate']))) {
					//             fps cannot be reached so return -1
					return - 1;
				}
				$frames_per_second = $info['video']['frame_rate'];
			}
			//         replace the frames
			$excess_frames = false;
			if ($has_frames) {
				$excess_frames = ceil(($input_seconds - $floored) * $frames_per_second);
				array_push($searches, '%fn');
				array_push($replacements, $excess_frames);
			}
			//         replace the total frames (ie frame number)
			if ($has_total_frames) {
				$round_frames = $floored * $frames_per_second;
				if (!$excess_frames) {
					$excess_frames = ceil(($input_seconds - $floored) * $frames_per_second);
				}
				array_push($searches, '%ft');
				array_push($replacements, $round_frames + $excess_frames);
			}
		}
		return str_replace($searches, $replacements, $return_format);
	}
	/*
	 * end of code borrowed from phpvideotoolkits
	 */
}
