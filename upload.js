(function($){
	$.fn.Uploader = function(options) {

		var defaults = {
			formdata : false
			
		},
		options = $.extend(defaults, options),
		$input = document.getElementById("images"), 
		methods = {
			upload: function() {
				console.log(window.FormData);
				// if (window.FormData) {
			 //  		options.formdata = new FormData();
			 //  		// document.getElementById("btn").style.display = "none";
				// }

				$input.addEventListener("change", function (evt) {
			 		document.getElementById("response").innerHTML = "Uploading . . .";
			 		$('#cancel').removeClass('hidden');
			 		var i = 0, len = this.files.length, img, reader, file;
				
					for ( ; i < len; i++ ) {
						file = this.files[i];
				
						if (!!file.type.match(/image.*/)) {
							if ( window.FileReader ) {
								reader = new FileReader();
								reader.onloadend = function (e) { 
									methods.preview(e.target.result, file.fileName);
								};
								reader.readAsDataURL(file);
							}
							if (options.formdata) {
								options.formdata.append("images[]", file);
							}
						}	
					}
				
					if (options.formdata) {
						console.log(options.formdata);
						$.ajax({
							url: "upload.php",
							type: "POST",
							data: options.formdata,
							processData: false,
							contentType: false,
							success: function (res) {
								document.getElementById("response").innerHTML = res; 
							}
						});
					}
				}, false);

				$('#cancel').click(function(){
					methods._cancelHandler();
				});
			},
			preview: function(source) {
		  		var list = document.getElementById("image-list"),
			  		li   = document.createElement("li"),
			  		img  = document.createElement("img");
			  		img.className = "upload-preview";
			  		
		  		img.src = source;
		  		li.appendChild(img);
				list.appendChild(li);

			},

			_cancelHandler: function (e) {
	            $('#image-list li').remove();
	            document.getElementById("response").innerHTML = "";
	            $('#cancel').addClass('hidden');
	        },

	        _uploadHandler: function(options) {
	        	console.log();
	        }

		}
		return methods.upload();
	}
})(jQuery);
