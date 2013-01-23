$(document).ready(function(){
	$('#images').Uploader({
		url : '/suploader/server/php/',
		autoUpload : true
	});
});