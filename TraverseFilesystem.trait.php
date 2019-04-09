<?php
trait TraverseFilesystem {

	public static function getFilesIn($dir = __DIR__, $recursive = TRUE) {
		if (!is_dir($dir)) {
			throw new Exception('Directory '.$dir.' not found.');
		}
		if (!is_readable($dir)) {
			throw new Exception('Directory '.$dir.' is not readable.');
		}
		$pattern = $dir.DIRECTORY_SEPARATOR.'*';
		$glob = glob($pattern);
		$files = array();
		foreach ($glob as $filename) {
			if (is_file($filename)) {
				$files[] = $filename;
			}
		}

		if (!$recursive) {return $files;}
		$dirs = self::getDirsIn($dir);
		foreach ($dirs as $dir) {
			$files[] = $dir;
			$glob = glob($dir.DIRECTORY_SEPARATOR.'*');
			foreach ($glob as $filename) {
				if (is_file($filename)) {
					$files[] = $filename;
				}
			}
		}
		return $files;
	}

	public static function getDirsIn($dir = __DIR__, $recursive = TRUE) {
		if (FALSE === ($handle = opendir($dir))) {die("Unable to read directory $dir.");}
		$files = array();
		while (FALSE !== ($file = readdir($handle))) {
			if ($file == '.' or $file == '..') {continue;}
			$full_filename = $dir.DIRECTORY_SEPARATOR.$file;
			if (is_dir($full_filename)) {
				$files[] = $full_filename;
				if ($recursive) {
					$files = array_merge($files,self::getDirsIn($full_filename));
				}
			}
		}
		closedir($handle);
		sort($files);
		return $files;
	}

	public static function standardizeFilename($filename) {
		$filename = str_replace('\\','/',$filename);
		return $filename;
	}

}




