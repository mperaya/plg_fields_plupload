<?php
/**
 * pluploadhandler.php
 *
 * Copyright 2013, Moxiecode Systems AB
 * Released under GPL License.
 * 
 * License: http://www.plupload.com/license/agplv3
 * @license    GNU Affero General Public License Version 3; http://www.gnu.org/licenses/agpl-3.0.txt 
 * Contributing: http://www.plupload.com/contributing
 *
 *  * Minor changes for joomla integration by Manuel P. Ayala
 */

namespace Mayala\Plugin\Fields\Plupload;


// Added for prevent use outside joomla calls
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Exception;
use Joomla\CMS\Filesystem\File;

//error_log("handler:\n",3,'/tmp/handler.log');

define('PLUPLOAD_MOVE_ERR', 103);
define('PLUPLOAD_INPUT_ERR', 101);
define('PLUPLOAD_OUTPUT_ERR', 102);
define('PLUPLOAD_TMPDIR_ERR', 100);
define('PLUPLOAD_TYPE_ERR', 104);
define('PLUPLOAD_UNKNOWN_ERR', 111);
define('PLUPLOAD_SECURITY_ERR', 105);
define('PLUPLOAD_FILE_EXIST_ERR', 106);

class PluploadHandler
{
	/**
	 * @property array $conf
	 */
	private $conf;

	/**
	 * Resource containing the reference to the file that we will write to.
	 * @property resource $out
	 */
	private $out;

	/**
	 * In case of the error, will contain error code.
	 * @property int [$error=null]
	 */
	protected $error = null;

	function __construct($conf = array())
	{
		// Get joomla app for url params check
		$app = 	Factory::getApplication();

		// Get url params for handler config
		$chunk    = $app->input->get('chunk', 0);
		$chunks   = $app->input->get('chunks', 0);
		$name     = $app->input->get('name', 0);
		
		$this->conf = array_merge(
			array(
				'file_data_name' => 'file',
				'tmp_dir' => ini_get("upload_tmp_dir") . "/plupload",
				'target_dir' => false,
				'cleanup' => true,
				'max_file_age' => 5 * 3600, // in hours
				'max_execution_time' => 5 * 60, // in seconds (5 minutes by default)
				'chunk' => $chunk,
				'chunks' => $chunks,
				'append_chunks_to_target' => true,
				'combine_chunks_on_complete' => true,
				'file_name' => $name,
				'allow_extensions' => false,
				'delay' => 0, // in seconds
				'cb_sanitize_file_name' => array($this, 'sanitize_file_name'),
				'cb_check_file' => false,
				'cb_filesize' => array($this, 'filesize'),
				// Define error strings from joomla references for joomla i18n support
				'error_strings' => array(
					PLUPLOAD_MOVE_ERR => Text::_("PLG_FIELDS_PLUPLOAD_MOVE_ERR"),
					PLUPLOAD_INPUT_ERR => Text::_("PLG_FIELDS_PLUPLOAD_INPUT_ERR"),
					PLUPLOAD_OUTPUT_ERR => Text::_("PLG_FIELDS_PLUPLOAD_OUTPUT_ERR"),
					PLUPLOAD_TMPDIR_ERR => Text::_("PLG_FIELDS_PLUPLOAD_TMPDIR_ERR"),
					PLUPLOAD_TYPE_ERR => Text::_("PLG_FIELDS_PLUPLOAD_TYPE_ERR"),
					PLUPLOAD_UNKNOWN_ERR => Text::_("PLG_FIELDS_PLUPLOAD_UNKNOWN_ERR"),
					PLUPLOAD_SECURITY_ERR => Text::_("PLG_FIELDS_PLUPLOAD_SECURITY_ERR"),
					PLUPLOAD_FILE_EXIST_ERR => Text::_("PLG_FIELDS_PLUPLOAD_FILE_EXIST_ERR")
					),
				'debug' => false,
				'log_path' => '/tmp/error.log'
				),
			$conf
			);
	}

	function __destruct()
	{
	    $this->reset();
	}

	function handleUpload()
	{
		$conf = $this->conf;

		$file_name = "";

		@set_time_limit($conf['max_execution_time']);

		try {
			// Start fresh
			$this->reset();

			// Cleanup outdated temp files and folders
			if ($conf['cleanup']) {
				$this->cleanup();
			}

			// Fake network congestion
			if ($conf['delay']) {
				sleep($conf['delay']);
			}

			if (!$conf['file_name']) {
				$currentfile = Factory::getApplication()->input->files->get('file');
//				if (!empty($_FILES)) {
				if (!empty($currentfile)) {
//					$conf['file_name'] = $_FILES[$conf['file_data_name']]['name'];
					$conf['file_name'] = $currentfile['name'];
				} else {
					throw new Exception('', PLUPLOAD_INPUT_ERR);
				}
			}

			if (is_callable($conf['cb_sanitize_file_name'])) {
				$file_name = call_user_func($conf['cb_sanitize_file_name'], $conf['file_name']);
			} else {
				$file_name = $conf['file_name'];
			}

			// Check if file type is allowed
			if ($conf['allow_extensions']) {
				if (is_string($conf['allow_extensions'])) {
					$conf['allow_extensions'] = preg_split('{\s*,\s*}', $conf['allow_extensions']);
				}

				if (!in_array(strtolower(pathinfo($file_name, PATHINFO_EXTENSION)), $conf['allow_extensions'])) {
					throw new Exception('', PLUPLOAD_TYPE_ERR);
				}
			}

			// Check if target files exists, if not upload it
			if (file_exists($this->getTargetPathFor($file_name))) {
				throw new Exception('', PLUPLOAD_FILE_EXIST_ERR);
			} else {
				// Check if success lock, else throw error
				if ($this->lockTheFile($file_name)) {
					$this->log("$file_name received" . ($conf['chunks'] ? ", chunks enabled: {$conf['chunk']} of {$conf['chunks']}" : ''));
					// Write file or chunk to appropriate temp location
					if ($conf['chunks']) {
						$result = $this->handleChunk($conf['chunk'], $file_name);
					} else {
						$result = $this->handleFile($file_name);
					}
					$this->unlockTheFile($file_name);
					return $result;
				} else {
					throw new Exception('', PLUPLOAD_OUTPUT_ERR);
				}
			}
		} catch (Exception $ex) {
			$this->error = $ex->getCode();
			$this->log("ERROR: " . $this->getErrorMessage());
			$this->unlockTheFile($file_name);
			return false;
		}
	}

	/**
	 * Retrieve the error code
	 *
	 * @return int Error code
	 */
	function getErrorCode()
	{
		if (!$this->error) {
			return null;
		}

		if (!isset($this->conf['error_strings'][$this->error])) {
			return PLUPLOAD_UNKNOWN_ERR;
		}

		return $this->error;
	}

	/**
	 * Retrieve the error message
	 *
	 * @return string Error message
	 */
	function getErrorMessage()
	{
		if ($code = $this->getErrorCode()) {
			return $this->conf['error_strings'][$code];
		} else {
			return '';
		}
	}

	/**
	 * Combine chunks for specified file name.
	 *
	 * @throws Exception In case of error generates exception with the corresponding code
	 *
	 * @param string $file_name
	 * @return string Path to the target file
	 */
	function combineChunksFor($file_name)
	{
		$file_path = $this->getTargetPathFor($file_name);
		if (!$tmp_path = $this->writeChunksToFile("$file_path.dir.part", "$file_path.part")) {
			return false;
		}
		return $this->rename($tmp_path, $file_path);
	}

	protected function handleChunk($chunk, $file_name)
	{
		$file_path = $this->getTargetPathFor($file_name);

		$this->log($this->conf['append_chunks_to_target']
			? "chunks being appended directly to the target $file_path.part"
			: "standalone chunks being written to $file_path.dir.part"
			);

		if ($this->conf['append_chunks_to_target']) {
			$chunk_path = $this->writeUploadTo("$file_path.part", false, 'ab');

			if ($this->isLastChunk($file_name)) {
				return $this->rename($chunk_path, $file_path);
			}
		} else {
			$chunk_path = $this->writeUploadTo("$file_path.dir.part" . "/$chunk.part");

			if ($this->conf['combine_chunks_on_complete'] && $this->isLastChunk($file_name)) {
				return $this->combineChunksFor($file_name);
			}
		}

		return array(
			'name' => $file_name,
			'path' => $chunk_path,
			'chunk' => $chunk,
			'size' => call_user_func($this->conf['cb_filesize'], $chunk_path)
			);
	}


	protected function handleFile($file_name)
	{
		$file_path = $this->getTargetPathFor($file_name);
		$tmp_path = $this->writeUploadTo($file_path . ".part");
		return $this->rename($tmp_path, $file_path);
	}


	protected function rename($tmp_path, $file_path)
	{
		try {
			// Upload complete write a temp file to the final destination
			if (!$this->fileIsOK($tmp_path)) {
				if ($this->conf['cleanup']) {
					@unlink($tmp_path);
				}
				throw new Exception('', PLUPLOAD_SECURITY_ERR);
			}

			if (rename($tmp_path, $file_path)) {
				$this->log("$tmp_path successfully renamed to $file_path");

				return array(
					'name' => basename($file_path),
					'path' => $file_path,
					'size' => call_user_func($this->conf['cb_filesize'], $file_path)
				);
			} else {
				throw new Exception('', PLUPLOAD_MOVE_ERR);
			}
		} catch (Exception $ex) {
			$this->error = $ex->getCode();
			$this->log("ERROR: " . $this->getErrorMessage());
			return false;
		}
	}


	/**
	 * Writes either a multipart/form-data message or a binary stream
	 * to the specified file.
	 *
	 * @throws Exception In case of error generates exception with the corresponding code
	 *
	 * @param string $file_path The path to write the file to
	 * @param string [$file_data_name='file'] The name of the multipart field
	 * @return string Path to the target file
	 */
	protected function writeUploadTo($file_path, $file_data_name = false, $mode = 'wb')
	{
		try {
			if (!$file_data_name) {
				$file_data_name = $this->conf['file_data_name'];
			}

			$base_dir = dirname($file_path);
			if (!file_exists($base_dir) && !@mkdir($base_dir, 0777, true)) {
				throw new Exception('', PLUPLOAD_TMPDIR_ERR);
			}

			$currentfile = Factory::getApplication()->input->files->get('file');
//			if (!empty($_FILES)) {
			if (!empty($currentfile)) {
//				if (!isset($_FILES[$file_data_name]) || $_FILES[$file_data_name]["error"] || !is_uploaded_file($_FILES[$file_data_name]["tmp_name"])) {
				if ($currentfile["error"] || !is_uploaded_file($currentfile["tmp_name"])) {
					throw new Exception('', PLUPLOAD_INPUT_ERR);
				}
//				return $this->writeToFile($_FILES[$file_data_name]["tmp_name"], $file_path, $mode);
				return $this->writeToFile($currentfile["tmp_name"], $file_path, $mode);
			} else {
				return $this->writeToFile("php://input", $file_path, $mode);
			}
		} catch (Exception $ex) {
			$this->error = $ex->getCode();
			$this->log("ERROR: " . $this->getErrorMessage());
			return false;
		}
	}

	/**
	 * Write source or set of sources to the specified target. Depending on the mode
	 * sources will either overwrite the content in the target or will be appended to
	 * the target.
	 *
	 * @param array|string $source_paths
	 * @param string $target_path
	 * @param string [$mode='wb'] Mode to use (to append use 'ab')
	 * @return string Path to the written target file
	 */
	protected function writeToFile($source_paths, $target_path, $mode = 'wb')
	{
		try {
			if (!is_array($source_paths)) {
				$source_paths = array($source_paths);
			}

			if (!$out = @fopen($target_path, $mode)) {
				throw new Exception('', PLUPLOAD_OUTPUT_ERR);
			}

			foreach ($source_paths as $source_path) {
				if (!$in = @fopen($source_path, "rb")) {
					throw new Exception('', PLUPLOAD_INPUT_ERR);
				}

				while ($buff = fread($in, 4096)) {
					fwrite($out, $buff);
				}

				@fclose($in);

				$this->log("$source_path " . ($mode == 'wb' ? "written" : "appended") . " to $target_path");
			}

			fflush($out);
			@fclose($out);

			return $target_path;
		} catch (Exception $ex) {
			$this->error = $ex->getCode();
			$this->log("ERROR: " . $this->getErrorMessage());
			return false;
		}
	}


	/**
	 * Combine chunks from the specified folder into the single file.
	 *
	 * @throws Exception In case of error generates exception with the corresponding code
	 *
	 * @param string $chunk_dir Directory containing the chunks
	 * @param string $target_path The file to write the chunks to
	 * @return string File path containing combined chunks
	 */
	protected function writeChunksToFile($chunk_dir, $target_path)
	{
		try {
			$chunk_paths = array();

			for ($i = 0; $i < $this->conf['chunks']; $i++) {
				$chunk_path = $chunk_dir . "/$i.part";
				if (!file_exists($chunk_path)) {
					throw new Exception('', PLUPLOAD_MOVE_ERR);
				}
				$chunk_paths[] = $chunk_path;
			}

			$this->writeToFile($chunk_paths, $target_path, 'ab');

			$this->log("$chunk_dir combined into $target_path");

			// Cleanup
			if ($this->conf['cleanup']) {
				$this->rrmdir($chunk_dir);
			}

			return $target_path;
		} catch (Exception $ex) {
			$this->error = $ex->getCode();
			$this->log("ERROR: " . $this->getErrorMessage());
			return false;
		}
	}

	/**
	 * Checks if currently processed chunk for the given filename is the last one.
	 *
	 * @param string $file_name
	 * @return boolean
	 */
	protected function isLastChunk($file_name)
	{
		if ($this->conf['append_chunks_to_target']) {
			if ($result = $this->conf['chunks'] && $this->conf['chunks'] == $this->conf['chunk'] + 1) {
				$this->log("last chunk received: {$this->conf['chunks']} out of {$this->conf['chunks']}");
			}
		} else {
			$file_path = $this->getTargetPathFor($file_name);
			$chunks = sizeof(glob("$file_path.dir.part/*.part"));
			if ($result = $chunks == $this->conf['chunks']) {
				$this->log("seems like last chunk ({$this->conf['chunk']}), 'cause there are $chunks out of {$this->conf['chunks']} *.part files in $file_path.dir.part.");
			}
		}

		return $result;
	}

	/**
	 * Runs cb_check_file filter on the file if defined in config.
	 *
	 * @param string $file_path Path to the file to check
	 * @return boolean
	 */
	protected function fileIsOK($path)
	{
		return !is_callable($this->conf['cb_check_file']) || call_user_func($this->conf['cb_check_file'], $path);
	}


	/**
	 * Returns the size of the file in bytes for the given filename. Filename will be resolved
	 * against target_dir value defined in the config.
	 *
	 * @param string $file_name
	 * @return number|false
	 */
	function getFileSizeFor($file_name)
	{
		return call_user_func($this->conf['cb_filesize'], getTargetPathFor($file_name));
	}


	/**
	 * Resolves given filename against target_dir value defined in the config.
	 *
	 * @param string $file_name
	 * @return string Resolved file path
	 */
	function getTargetPathFor($file_name)
	{
		$target_dir = rtrim($this->conf['target_dir'], "/\\");
		$target_dir = str_replace(array("/", "\/"), "/", $target_dir);
		return $target_dir . "/" . $file_name;
	}


	/**
	 * Sends out headers that prevent caching of the output that is going to follow.
	 */
	function sendNoCacheHeaders()
	{
		// Make sure this file is not cached (as it might happen on iOS devices, for example)
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
//		header('Clear-Site-Data: "cache", "storage"');
	}

	/**
	 * Handles CORS.
	 *
	 * @param array $headers Additional headers to send out
	 * @param string [$origin='*'] Allowed origin
	 */
	function sendCORSHeaders($headers = array(), $origin = '*')
	{
		$allow_origin_present = false;

		if (!empty($headers)) {
			foreach ($headers as $header => $value) {
				if (strtolower($header) == 'access-control-allow-origin') {
				    $allow_origin_present = true;
				}
				header("$header: $value");
			}
		}

		if ($origin && !$allow_origin_present) {
			header("Access-Control-Allow-Origin: $origin");
		}

		// other CORS headers if any...
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			exit; // finish preflight CORS requests here
		}
	}

	/**
	 * Purge current file
	 */
	public function purge()
	{
		$file = $this->conf['file_name'] ? $this->conf['target_dir'] . '/' . $this->sanitizeFileName2($this->conf['file_name']) : '';
//error_log("file: ".print_r($file,true)."\n", 3,'/tmp/plupload.log');

		try {
			File::delete($file);
		} catch (Exception $ex) {
			$this->error = $ex->getCode();
			$this->log("ERROR: " . $this->getErrorMessage());
			return false;
		}
	}

        public function download()
        {
		$file = $this->conf['file_name'] ? $this->conf['target_dir'] . '/' . $this->sanitizeFileName2($this->conf['file_name']) : '';

		$len = filesize( $file );

		// Begin writing headers
		header( 'Pragma: public' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: pre-check=0, post-check=0, max-age=0' );
		header( 'Expires: 0' );
		// Use the desired Content-Type
		header( 'Content-Type: application/octet-stream');
		// Force the download
		header( 'Content-Disposition: attachment; filename="' . $this->conf['file_name'] . '"' );
		header( 'Content-Transfer-Encoding: binary');
		header( 'Content-Length: ' . $len );
		@readfile( $file );
		exit;
        }

	/**
	 * Cleans up outdated *.part files and directories inside target_dir.
	 * Files are considered outdated if they are older than max_file_age hours.
	 * (@see config options)
	 */
	private function cleanup()
	{
		// Remove old temp files
		if (file_exists($this->conf['target_dir'])) {
			foreach (glob($this->conf['target_dir'] . '/*.part') as $tmpFile) {
				if (time() - filemtime($tmpFile) < $this->conf['max_file_age']) {
					continue;
				}
				if (is_dir($tmpFile)) {
					self::rrmdir($tmpFile);
				} else {
					@unlink($tmpFile);
				}
			}
		}
	}


	/**
	 * Sanitizes a filename replacing whitespace with dashes
	 *
	 * Removes special characters that are illegal in filenames on certain
	 * operating systems and special characters requiring special escaping
	 * to manipulate at the command line. Replaces spaces and consecutive
	 * dashes with a single dash. Trim period, dash and underscore from beginning
	 * and end of filename.
	 *
	 * @author WordPress
	 *
	 * @param string $filename The filename to be sanitized
	 * @return string The sanitized filename
	 */
	protected function sanitizeFileName($filename)
	{
		$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
		$filename = str_replace($special_chars, '', $filename);
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = trim($filename, '.-_');
		return $filename;
	}

	protected function sanitizeFileName2($filename)
	{
		$special_chars = array(" ","?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
		$filename = str_replace($special_chars, '', $filename);
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = trim($filename, '.-_');
		return $filename;
	}

	/**
	 * Concise way to recursively remove a directory
	 * @see http://www.php.net/manual/en/function.rmdir.php#108113
	 *
	 * @param string $dir Directory to remove
	 */
	private function rrmdir($dir)
	{
		foreach (glob($dir . '/*') as $file) {
			if (is_dir($file)) {
				$this->rrmdir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dir);
	}


	/**
	 * PHPs filesize() fails to measure files larger than 2gb
	 * @see http://stackoverflow.com/a/5502328/189673
	 *
	 * @param string $file Path to the file to measure
	 * @return int
	 */
	protected function filesize($file)
	{
		if (!file_exists($file)) {
			$this->log("cannot measure $file, 'cause it doesn't exist.");
			return false;
		}

		static $iswin;
		if (!isset($iswin)) {
			$iswin = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
		}

		static $exec_works;
		if (!isset($exec_works)) {
			$exec_works = (function_exists('exec') && !ini_get('safe_mode') && @exec('echo EXEC') == 'EXEC');
		}

		// try a shell command
		if ($exec_works) {
			$cmd = ($iswin) ? "for %F in (\"$file\") do @echo %~zF" : "stat -c%s \"$file\"";
			@exec($cmd, $output);
			if (is_array($output) && is_numeric($size = trim(implode("\n", $output)))) {
				$this->log("filesize obtained via exec.");
				return $size;
			}
		}

		// try the Windows COM interface
		if ($iswin && class_exists("COM")) {
			try {
				$fsobj = new COM('Scripting.FileSystemObject');
				$f = $fsobj->GetFile(realpath($file));
				$size = $f->Size;
			} catch (Exception $e) {
				$size = null;
			}
			if (ctype_digit($size)) {
				$this->log("filesize obtained via Scripting.FileSystemObject.");
				return $size;
			}
		}

		// if everything else fails
		$this->log("filesize obtained via native filesize.");
		return @filesize($file);
	}


	/**
	 * Obtain the blocking lock on the specified file. All processes looking to work with
	 * the same file will have to wait, until we release it (@see unlockTheFile).
	 *
	 * @param string $file_name File to lock
	 */
	private function lockTheFile($file_name)
	{
		$file_path = $this->getTargetPathFor($file_name);
		$this->out = fopen("$file_path.lock", 'w');
		if (is_resource($this->out)) {
			flock($this->out, LOCK_EX); // obtain blocking lock
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Release the blocking lock on the specified file.
	 *
	 * @param string $file_name File to lock
	 */
	private function unlockTheFile($file_name)
	{
		$file_path = $this->getTargetPathFor($file_name);
		if (is_resource($this->out)) {
			fclose($this->out);
			@unlink("$file_path.lock");
		}
	}


	/**
	 * Reset private variables to their initial values.
	 */
	private function reset()
	{
		$conf = $this->conf;
		$this->error = null;

		if (is_resource($this->out)) {
			fclose($this->out);
		}
	}


	/**
	 * Log the message to the log_path, but only if debug is set to true.
	 * Each message will get prepended with the current timestamp.
	 *
	 * @param string $msg
	 */
	protected function log($msg)
	{
		if (!$this->conf['debug']) {
			return;
		}
		$msg = date("Y-m-d H:i:s") . ": $msg\n";
		file_put_contents($this->conf['log_path'], $msg, FILE_APPEND);
	}
}
