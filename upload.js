(function($){
	$.fn.Uploader = function(options) {

		var defaults = {
			formdata : false,
            multiple : true,
            autoUpload : true
		},
		options = $.extend(defaults, options),
		$input = document.getElementById("images"), 
		methods = {
			upload: function() {
                // set Attribute single / multiple upload to
                // input files
                var btn = document.getElementById("images")
                if (options.multiple){
                    btn.setAttribute("multiple", "multiple");
                }

                options.formdata = new FormData();
                $input.addEventListener("change", function (evt) {
			 		var i = 0, len = this.files.length, img, reader, file;
				
					for ( ; i < len; i++ ) {
						file = this.files[i];

						if (!!file.type.match(/image.*/)) {
							if ( window.FileReader ) {
								reader = new FileReader();
								reader.onloadend = function (e) {

                                    // append files images to formdata                                  
                                    if (options.formdata) {
								        options.formdata.append("images[]", file);
							        }
                                    
                                    console.log(options.formdata);
                                    if (options.autoUpload){
                                        // if autoUpload is TRUE submit formdata.
                                        // preview download.
                                        methods._uploadHandler(options);
                                        methods.preview_download(e.target.result, file.fileName);

                                    }else{
                                        // if autoUpload is FALSE.
                                        // preview download.
                                        // show cancel
									    methods.preview(e.target.result, file.fileName);

                                    }

								};
								reader.readAsDataURL(file);
							}
						}	
					}
				}, false);
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

            preview_download: function(source) {
		  		console.log();
			},

			_cancelHandler: function (e) {
	            $('#image-list li').remove();
	            document.getElementById("response").innerHTML = "";
	            $('#cancel').addClass('hidden');
	        },

	        _uploadHandler: function(options) {
	        	if (options.formdata){
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
	        }

		}
		return methods.upload();
	}
})(jQuery);
