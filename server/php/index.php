<?php 
	include('uploadHandler.php');
	
	if (isset($_FILES)) {
		$upload = new UploadHandler();	
	}
	
	