(function($){
	$.fn.Uploader = function(options) {

		var defaults = {
			formdata : false,
            multiple : true,
            autoUpload : true
		},
		option_upload = $.extend(defaults, options),
		$input = document.getElementById("images"), 
		methods = {
			upload: function() {
                // set Attribute single / multiple upload to
                // input files
                var btn = document.getElementById("images");

                if (option_upload.multiple){
                    btn.setAttribute("multiple", "multiple");
                }

                if (option_upload.autoUpload){
                    $('#btn').hide();
                }

                option_upload.formdata = new FormData();
                $input.addEventListener("change", function (evt) {
			 		var i = 0, len = this.files.length, img, reader, file;

					for ( ; i < len; i++ ) {
						file = this.files[i];

						if (!!file.type.match(/image.*/)) {
							if ( window.FileReader ) {
								reader = new FileReader();
								reader.onloadend = function (e) {

                                    // append files images to formdata
                                    if (option_upload.formdata) {
								        option_upload.formdata.append("images[]", file);
							        }
                                    
                                    if (option_upload.autoUpload){
                                        // if autoUpload is TRUE submit formdata.
                                        // preview download.
                                        methods._uploadHandler(option_upload);
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
			  		img  = document.createElement("img"),
                    a = document.createElement('a');

                a.innerHTML = 'cancel';
                a.href = '#';
			  	img.className = "upload-preview";

                img.src = source;
		  		li.appendChild(img);
				list.appendChild(li);

			},

            preview_download: function(source) {
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

	        _uploadHandler: function(option_upload) {
	        	if (option_upload.formdata){
					$.ajax({
					    url: "upload.php",
					    type: "POST",
					    data: option_upload.formdata,
						processData: false,
					    contentType: false,
					    success: function (res) {
						    document.getElementById("response").innerHTML = res;
						}
					});
				}
	        }
		};
		return methods.upload();
	};
})(jQuery);