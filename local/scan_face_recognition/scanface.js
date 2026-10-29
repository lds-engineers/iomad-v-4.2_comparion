const getTimeout = () => 5000;
let verifiedCount = 0;
const verifiedLimit = 3;

//--------------------
// GET USER MEDIA CODE
//--------------------

var canvas, ctx;

function init() {
    const video = document.createElement('video');
    video.id = 'video';
    video.autoplay = true;
    video.muted = true;
    video.style = 'opacity: 0; position: fixed;';
    const div = document.createElement('div');
    div.id = 'canvas-wrapper';
    $('#alert-wrap').append(video);
    $('#alert-wrap').append(div);
}

validateFace = function (callback) {
    verifiedCount++;
    const form = new FormData();
    form.append("referenceImage", CFG.wwwroot + "/local/scan_face_recognition/temp/" + lessonData.image);
    const block = snapshot(true).split(";");
    const real = block[1].split(",")[1];
    form.append('currentImage', real);
    form.append('courseid', courseid);
    $.ajax({
        url: CFG.wwwroot +'/local/scan_face_recognition/post.php',
        type: 'POST',
        contentType: false,
        processData: false,
        data: form,
        success: function (response) {
            const resp = JSON.parse(response);
            console.log('txt----',resp);
            if (resp && resp.Succeeded && resp.Succeeded.transaction_status) {
                callback(true);
            } else {
                if (verifiedCount < verifiedLimit) {
                    setTimeout(() => {
                        validateFace(callback);
                    }, 5000)
                } else {
                    callback(false);
                }
            }
        },
        error: function (err) {
            console.error(err);
            callback(false);
        }
    });
};

removeVideo = function () {
    $('iframe').remove();
};

removeQuestion = function () {
    $('.answer-option').remove();
    $('.lp-quiz-buttons').remove();
    $('.question-numbers').remove();
}

removeScanFace = function() {
    $('#alert-wrap').children('video').remove();
    $('#alert-wrap').children('#canvas-wrapper').remove();
}

$(document).ready(() => {
    const companyHost = companyDetails ? companyDetails.data.hostname : server
    const allowedHosts = [companyHost];
    if (allowedHosts.indexOf(window.location.host) === -1) {
        console.error('[ScanFaceVerification] This is not allowed to run on this website.');
    } else {
        init();
        setTimeout(() => {
            startWebcam()
                .then(() => {
                    validateFace((valid) => {
                        if (valid) {
                            stopWebcam();
                        } else {
                            showError('<a onclick="window.location.reload();">Your facial biometrics have not been verified. Please click here to restart the lesson. Biometric verification will be repeated.</a>', true);
                            stopWebcam();
                            removeScanFace();
                            if(lessonData) {
                                if (lessonData.postType === 'lp_lesson') {
                                    removeVideo();
                                }
                                if (lessonData.postType === 'lp_question') {
                                    removeQuestion();
                                }
                            }
                        }
                    });
                })
                .catch((error) => {
                    showError(error);
                    removeVideo();
                })
        }, getTimeout())
    }
});

