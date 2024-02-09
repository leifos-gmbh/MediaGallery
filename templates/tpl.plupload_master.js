// Convert divs to queue widgets when the DOM is ready
//window.onload=function() {
$(function() {
    $("#uploader").pluploadQueue({
        // General settings
        runtimes : 'html5,flash',
        url : '{UPLOAD_URL}',
        max_file_size : '{MAX_FILE_SIZE}',
        chunk_size : '1mb',
        unique_names : false,
        {FILTERS}

        // Flash settings
        flash_swf_url : '/plupload/js/plupload.flash.swf',

            // Silverlight settings
        silverlight_xap_url : '/plupload/js/plupload.silverlight.xap',



        init: {
            UploadComplete: function(up, files) {

                let message = "&upload=successfully";

                if(window.location.href.indexOf(message) > -1)
                {
                    message = '';
                }

                let url = window.location.href.replace("cmd=upload", "cmd=mediafiles");
                window.location = url + message;
            },
        }
    });

    // Client side form validation
    $('form').submit(function(e) {
        var uploader = $('#uploader').pluploadQueue();

        // Files in queue upload them first
        if (uploader.files.length > 0) {
            // When all files are uploaded submit form
            uploader.bind('StateChanged', function() {
                if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
                    $('form')[0].submit();
                }
            });

            uploader.start();
        } else {
            alert('{FILE_ALERT}');
        }

        return false;
    });
});
