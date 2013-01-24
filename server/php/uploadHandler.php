<?php
	Class UploadHandler {

		//protected $_http_methods = array('GET', 'POST', 'PUT', 'DELETE');
		protected $options;
		public function __construct($options = null, $initialize = true)
		{
			// set parameter default
			$this->options = array(
				'param_name' => 'images',
				'upload_dir' => dirname($_SERVER['SCRIPT_FILENAME']).'/files/',
				'upload_url' => $this->get_full_url().'/files/',
				// Set the following option to false to enable resumable uploads:
            	'discard_aborted_uploads' => true,
            	// Defines which files (based on their names) are accepted for upload:
            	'accept_file_types' => '/.+$/i',
            	// The php.ini settings upload_max_filesize and post_max_size
	            // take precedence over the following max_file_size setting:
	            'max_file_size' => null,
	            'min_file_size' => 1,
	            // The maximum number of files for the upload directory:
            	'max_number_of_files' => null,
            	// Image resolution restrictions:
	            'max_width' => null,
	            'max_height' => null,
	            'min_width' => 1,
	            'min_height' => 1,
	            // Set the following option to 'POST', if your server does not support
	            // DELETE requests. This is a parameter sent to the client:
	            'delete_type' => 'DELETE',
	            'access_control_allow_origin' => '*',
	            'access_control_allow_credentials' => false,
	            'access_control_allow_methods' => array(
	                'OPTIONS',
	                'HEAD',
	                'GET',
	                'POST',
	                'PUT',
	                'DELETE'
	            ),
	            'access_control_allow_headers' => array(
	                'Content-Type',
	                'Content-Range',
	                'Content-Disposition',
	                'Content-Description'
	            )
			);

			if (isset($options)) {

				$this->options = array_merge($this->options, $options);
			}
			if($initialize){
				$this->initialize();
			}
		}

		protected function get_full_url() {
	        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
	        return
	            ($https ? 'https://' : 'http://').
	            (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
	            (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
	            ($https && $_SERVER['SERVER_PORT'] === 443 ||
	            $_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
	            substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
	    }

		protected function initialize() {
			switch ($_SERVER['REQUEST_METHOD']) {
	            case 'OPTIONS':
	            case 'HEAD':
	                $this->head();
	                break;
	            case 'GET':
	                $this->get();
	                break;
	            case 'POST':
	                $this->post();
	                break;
	            case 'DELETE':
	                $this->delete();
	                break;
	            default:
	                header('HTTP/1.1 405 Method Not Allowed');
	        }
		}

		protected function handle_upload($tmpFile, $name, $size, $type, $error, $index = null){

			$file = new stdClass();
			$file->name = $this->trim_file_name($name, $type, $index);
			$file->size = $this->fix_integer_overflow(intval($size));
			$file->type = $type;

			if($this->validate($tmpFile, $file, $error, $index)){
				$upload_dir = $this->get_upload_path();
				// die(var_dump(!is_dir($upload_dir)));
				if(!is_dir($upload_dir)) {
					
					if (!mkdir($upload_dir, 0777, true)) {
				        die('Failed to create folders...');
				    }
					//mkdir($upload_dir);
					
				}

				$file_path = $this->get_upload_path($file->name);
				$append_file = is_file($file_path) && $file->size > $this->get_file_size($file_path);

				if($tmpFile && is_uploaded_file($tmpFile)) {
					
					if($append_file) {
						file_put_contents(
	                        $file_path,
	                        fopen($tmpFile, 'r'),
	                        FILE_APPEND
	                    );
					}else{
						move_uploaded_file($tmpFile, $file_path);
					}

				}else{
					// Non-multipart uploads (PUT method support)
	                file_put_contents(
	                    $file_path,
	                    fopen('php://input', 'r'),
	                    $append_file ? FILE_APPEND : 0
	                );
				}

				$file_size = $this->get_file_size($file_path, $append_file);
				if($file_size === $file->size) {
					
					$file->url = $this->options['upload_url'].rawurlencode($file->name);

				}else if ($this->options['discard_aborted_uploads']) {

					unlink($file_path);
                	$file->error = 'abort';

				}
			}

			return $file;	

		}

		protected function post($print_response = true) {
			$files = isset($_FILES[$this->options['param_name']]) ? $_FILES[$this->options['param_name']] : null;
            $content_range = isset($_SERVER['HTTP_CONTENT_RANGE']) ?
            	preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']) : null;
           	

			if ($files != null && is_array($files['tmp_name'])) {
				// files is a multi-dimensional array

				foreach ($files['tmp_name'] as $key => $value) {
					$file_info[] = $this->handle_upload(
						$files['tmp_name'][$key],
						$files['name'][$key],
						$files['size'][$key],
					    $files['type'][$key],
						$files['error'][$key],
						$key
					);
				}

			}else{
				$file_info[] = $this->handle_upload(
						$files['tmp_name'],
						$files['name'],
						$files['size'],
					    $files['type'],
						$files['error'],
						null
					);
			}

			return $this->generate_response($file_info, $print_response);
		}

		protected function trim_file_name($name, $type, $index) {
	        // Remove path information and dots around the filename, to prevent uploading
	        // into different directories or replacing hidden system files.
	        // Also remove control characters and spaces (\x00..\x20) around the filename:
	        $file_name = trim(basename(stripslashes($name)), ".\x00..\x20");
	        // Add missing file extension for known image types:
	        if (strpos($file_name, '.') === false &&
	            preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
	            $file_name .= '.'.$matches[1];
	        }
	        while(is_dir($this->get_upload_path($file_name))) {
	            $file_name = $this->upcount_name($file_name);
	        }
	        
	        return $file_name;
	    }

	    // Fix for overflowing signed 32 bit integers,
	    // works for sizes up to 2^32-1 bytes (4 GiB - 1):
	    protected function fix_integer_overflow($size) {
	        if ($size < 0) {
	            $size += 2.0 * (PHP_INT_MAX + 1);
	        }
	        return $size;
	    }

	    protected function validate($uploaded_file, $file, $error, $index) {
	        if ($error) {
	            $file->error = $this->get_error_message($error);
	            return false;
	        }
	        $content_length = $this->fix_integer_overflow(intval($_SERVER['CONTENT_LENGTH']));
	        if ($content_length > $this->get_config_bytes(ini_get('post_max_size'))) {
	            $file->error = $this->get_error_message('post_max_size');
	            return false;
	        }
	        if (!preg_match($this->options['accept_file_types'], $file->name)) {
	            $file->error = $this->get_error_message('accept_file_types');
	            return false;
	        }
	        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
	            $file_size = $this->get_file_size($uploaded_file);
	        } else {
	            $file_size = $content_length;
	        }
	        if ($this->options['max_file_size'] && (
	                $file_size > $this->options['max_file_size'] ||
	                $file->size > $this->options['max_file_size'])
	            ) {
	            $file->error = $this->get_error_message('max_file_size');
	            return false;
	        }
	        if ($this->options['min_file_size'] &&
	            $file_size < $this->options['min_file_size']) {
	            $file->error = $this->get_error_message('min_file_size');
	            return false;
	        }
	        if (is_int($this->options['max_number_of_files']) && (
	                $this->count_file_objects() >= $this->options['max_number_of_files'])
	            ) {
	            $file->error = $this->get_error_message('max_number_of_files');
	            return false;
	        }
	        list($img_width, $img_height) = @getimagesize($uploaded_file);
	        if (is_int($img_width)) {
	            if ($this->options['max_width'] && $img_width > $this->options['max_width']) {
	                $file->error = $this->get_error_message('max_width');
	                return false;
	            }
	            if ($this->options['max_height'] && $img_height > $this->options['max_height']) {
	                $file->error = $this->get_error_message('max_height');
	                return false;
	            }
	            if ($this->options['min_width'] && $img_width < $this->options['min_width']) {
	                $file->error = $this->get_error_message('min_width');
	                return false;
	            }
	            if ($this->options['min_height'] && $img_height < $this->options['min_height']) {
	                $file->error = $this->get_error_message('min_height');
	                return false;
	            }
	        }
	        return true;
	    }

	    protected function get_upload_path($file_name = null, $version = null) {
	        $file_name = $file_name ? $file_name : '';
	        $version_path = empty($version) ? '' : $version.'/';
	        return $this->options['upload_dir'].$version_path.$file_name;
	    }

	    protected function get_file_size($file_path, $clear_stat_cache = false) {
	        if ($clear_stat_cache) {
	            clearstatcache();
	        }
	        return $this->fix_integer_overflow(filesize($file_path));

	    }

	    protected function get_config_bytes($val) {
	        $val = trim($val);
	        $last = strtolower($val[strlen($val)-1]);
	        switch($last) {
	            case 'g':
	                $val *= 1024;
	            case 'm':
	                $val *= 1024;
	            case 'k':
	                $val *= 1024;
	        }
	        return $this->fix_integer_overflow($val);
	    }

	    public function head() {
	        header('Pragma: no-cache');
	        header('Cache-Control: no-store, no-cache, must-revalidate');
	        header('Content-Disposition: inline; filename="files.json"');
	        // Prevent Internet Explorer from MIME-sniffing the content-type:
	        header('X-Content-Type-Options: nosniff');
	        if ($this->options['access_control_allow_origin']) {
	           $this->send_access_control_headers();
	        }
	        $this->send_content_type_header();
	    }

	    protected function send_content_type_header() {
	        header('Vary: Accept');
	        if (isset($_SERVER['HTTP_ACCEPT']) &&
	            (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
	            header('Content-type: application/json');
	        } else {
	            header('Content-type: text/plain');
	        }
	    }

	    protected function send_access_control_headers() {
	        header('Access-Control-Allow-Origin: '.$this->options['access_control_allow_origin']);
	        header('Access-Control-Allow-Credentials: '
	            .($this->options['access_control_allow_credentials'] ? 'true' : 'false'));
	        header('Access-Control-Allow-Methods: '
	            .implode(', ', $this->options['access_control_allow_methods']));
	        header('Access-Control-Allow-Headers: '
	            .implode(', ', $this->options['access_control_allow_headers']));
	    }

	    protected function generate_response($content, $print_response = true) {
	        if ($print_response) {
	            $json = json_encode($content);
	            $redirect = isset($_REQUEST['redirect']) ?
	                stripslashes($_REQUEST['redirect']) : null;
	            if ($redirect) {
	                header('Location: '.sprintf($redirect, rawurlencode($json)));
	                return;
	            }
	            $this->head();
	            if (isset($_SERVER['HTTP_CONTENT_RANGE']) && is_array($content) &&
	                    is_object($content[0]) && $content[0]->size) {
	                header('Range: 0-'.($this->fix_integer_overflow(intval($content[0]->size)) - 1));
	            }
	            echo $json;
	        }
	        return $content;
	    }
	}
?>