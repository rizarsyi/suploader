(function($){
	$.fn.Uploader = function(options) {

		var defaults = {
			url : false,
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

                                    if (option_upload.autoUpload){
                                        // if autoUpload is TRUE submit formdata.
                                        // preview download.
                                        methods._uploadHandler(option_upload);
                                        //methods.preview_download(e.target.result, file.fileName);

                                    }else{
                                        // if autoUpload is FALSE.
                                        // preview upload.
                                        // show cancel
										methods.preview(e.target.result, file.fileName);

                                    }

								};
								reader.readAsDataURL(file);
							}

                            // append files images to formdata
                            if (option_upload.formdata) {
								option_upload.formdata.append("images[]", file);
                            }
						}
					}
				}, false);
			},

			preview: function(source, file) {
                //console.log(file);
                var list = document.getElementById("image-list"),
					li   = document.createElement("li"),
					img  = document.createElement("img"),
                    a = document.createElement('a');

                a.innerHTML = 'cancel';
                a.href = '#';
                a.id = 'cancel';
				img.className = "upload-preview";

                img.src = source;
				li.appendChild(img);
                li.appendChild(a);
				list.appendChild(li);
                
                $('#cancel').click(function(){
                    methods._cancelHandler();
				});

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

			_uploadHandler: function(options) {

				if (options.formdata){
					$.ajax({
						url: options.url,
						type: "POST",
						data: option_upload.formdata,
						processData: false,
						contentType: false,
						success: function (result) {
							var data = $.parseJSON(result);
							console.log(data);
							document.getElementById("response").innerHTML = res;
						}
					});
				}
			}
		};
		return methods.upload();
	};
})(jQuery);
