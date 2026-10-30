function removeEverything() {
    $('#camera-wrapper').remove();
}

function b64toBlob(b64Data, contentType, sliceSize) {
    contentType = contentType || '';
    sliceSize = sliceSize || 512;
    var byteCharacters = atob(b64Data);
    var byteArrays = [];
    for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        var slice = byteCharacters.slice(offset, offset + sliceSize);
        var byteNumbers = new Array(slice.length);
        for (var i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }
        var byteArray = new Uint8Array(byteNumbers);
        byteArrays.push(byteArray);
    }
    var blob = new Blob(byteArrays, { type: contentType });
    return blob;
}

const uploadFile = function (base64) {
    return new Promise((resolve, reject) => {
        var block = base64.split(";");
        var contentType = block[0].split(":")[1];
        var realData = block[1].split(",")[1];
        const file = b64toBlob(realData, contentType);
        const timestamp = Date.now();
        const form_data = new FormData();
        form_data.append('file', file, `scanfacerefimg_${timestamp}_${aw.id}.jpg`);
        form_data.append('action', 'file_upload');
        form_data.append('courseid', courseid);
        $.ajax({
            url: CFG.wwwroot +'/local/scan_face_recognition/post.php',
            type: 'POST',
            contentType: false,
            processData: false,
            data: form_data,
            success: resolve,
            error: reject
        });
    });
};

var canvas, ctx;
function init() {
    $('#saveBtn').hide();
    const video = document.createElement('video');
    video.id = 'video';
    video.autoplay = true;
    video.muted = true;
    $('#video-wrapper').append(video);
}

let imgData, redirectPathname, saving;
$(document).ready(() => { 
    const companyHost = companyDetails ? companyDetails.data.hostname : server
    const allowedHosts = [companyHost];
    if (allowedHosts.indexOf(window.location.host) === -1) {
        $('body').remove();
    } else {
        const urlParams = new URLSearchParams(window.location.search);
        redirectPathname = urlParams.get('id');
        init();
        startWebcam()
        .then(() => {
            $('#snapshotBtn').click(function () {
                imgData = snapshot();
                $('#saveBtn').show();
            });
            $('#saveBtn').click(function () {
                if (imgData && !saving) {
                    saving = true;
					$(".lp_scansave_imgloader").show();
                    uploadFile(imgData).then(() => {
						$(".lp_scansave_imgloader").hide();
                        removeEverything();
                        stopWebcam();
                        showSuccessMessage('Upload successful. Please wait while your photo is being processed. You will be redirected to the course page shortly where you need to upload document (Driver License)');
                        window.location.href = CFG.wwwroot +'/local/scan_face_recognition/uploaddocument.php?id='+ redirectPathname;
                    });
                }
            });
        })
        .catch(function (err) {
            showError(err);
        })
    }
});

