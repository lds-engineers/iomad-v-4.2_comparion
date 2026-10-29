var video;
var webcamStream;

function startWebcam() {
    return new Promise((resolve, reject) => {

        const constraints = { audio: false, video: true };

        navigator.mediaDevices.getUserMedia(constraints)
            .then((localMediaStream) => {
                video = document.querySelector('video');
                video.onloadedmetadata = resolve;
                video.muted = true;
                video.playsInline = true;
                video.srcObject = localMediaStream;
                webcamStream = localMediaStream;
            })
            .catch((err) => {
                console.log(err);
                reject('Camera is not supported.')
            })
    });
}

function stopWebcam() {
    const tracks = webcamStream.getTracks();

    for (let idx = 0; idx < tracks.length; idx++) {
        tracks[idx].stop();
    }
}

function snapshot(hidden) {

    $('canvas').remove();

    const canvasEl = document.createElement('canvas');
    canvasEl.id = 'myCanvas';
    canvasEl.width = video.offsetWidth;
    canvasEl.height = video.offsetHeight;
    
    if (hidden) {
        canvasEl.style = 'opacity: 0; position: fixed;';
    }

    $('#canvas-wrapper').append(canvasEl);

    const canvas = document.getElementById("myCanvas");
    const ctx = canvas.getContext('2d');

    ctx.drawImage(video, 0, 0, video.offsetWidth, video.offsetHeight);
    return canvas.toDataURL("image/jpeg");
}
