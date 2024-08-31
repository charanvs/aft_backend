<style>
    /* Your existing styles plus additional styles for the second progress bar */
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 20px;
    }

    form {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        margin: auto;
    }

    label {
        font-size: 16px;
        color: #333333;
        margin-bottom: 10px;
        display: block;
    }

    input[type="file"] {
        font-size: 16px;
        padding: 10px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        width: 100%;
        margin-bottom: 20px;
    }

    button {
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
    }

    button:hover {
        background-color: #0056b3;
    }

    #imageProgressWrapper, #textProgressWrapper {
        margin-top: 20px;
        display: none;
    }

    #imageProgressBar, #textProgressBar {
        width: 100%;
        height: 20px;
        border-radius: 4px;
        background-color: #e9ecef;
        overflow: hidden;
    }

    #imageProgressBar::-webkit-progress-value,
    #textProgressBar::-webkit-progress-value {
        background-color: #28a745;
    }

    #imageProgressPercent, #textProgressPercent {
        font-size: 16px;
        color: #555555;
        text-align: center;
        margin-top: 10px;
        display: block;
    }

    .alert {
        margin-top: 20px;
        padding: 10px;
        border-radius: 4px;
        color: white;
        display: none;
        text-align: center;
    }

    .alert.success {
        background-color: #28a745;
    }

    .alert.error {
        background-color: #dc3545;
    }

    #resultText {
        margin-top: 20px;
        padding: 20px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        font-size: 16px;
        color: #333333;
        display: none;
    }

    #downloadLink {
        margin-top: 20px;
        display: none;
        text-align: center;
    }

    #downloadLink a {
        text-decoration: none;
        background-color: #28a745;
        color: white;
        padding: 10px 20px;
        border-radius: 4px;
        font-size: 16px;
    }

    #downloadLink a:hover {
        background-color: #218838;
    }
</style>

<form action="{{ route('convert.pdf.to.text') }}" method="POST" enctype="multipart/form-data" id="pdfUploadForm">
    @csrf
    <div>
        <label for="pdf_file">Upload PDF:</label>
        <input type="file" name="pdf_file" id="pdf_file" required>
    </div>
    <div>
        <button type="submit">Convert to Text</button>
    </div>
    <div id="imageProgressWrapper">
        <progress id="imageProgressBar" value="0" max="100"></progress>
        <span id="imageProgressPercent">0%</span>
    </div>
    <div id="textProgressWrapper">
        <progress id="textProgressBar" value="0" max="100"></progress>
        <span id="textProgressPercent">0%</span>
    </div>
    <div class="alert success" id="successAlert">File uploaded and conversion complete!</div>
    <div class="alert error" id="errorAlert">There was an error with your upload. Please try again.</div>
</form>

<div id="resultText"></div>
<div id="downloadLink"></div>

<script>
    document.getElementById('pdfUploadForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        
        let form = event.target;
        let formData = new FormData(form);
        let imageProgressBar = document.getElementById('imageProgressBar');
        let textProgressBar = document.getElementById('textProgressBar');
        let imageProgressWrapper = document.getElementById('imageProgressWrapper');
        let textProgressWrapper = document.getElementById('textProgressWrapper');
        let imageProgressPercent = document.getElementById('imageProgressPercent');
        let textProgressPercent = document.getElementById('textProgressPercent');
        let successAlert = document.getElementById('successAlert');
        let errorAlert = document.getElementById('errorAlert');
        let resultText = document.getElementById('resultText');
        let downloadLink = document.getElementById('downloadLink');

        // Hide alerts, result text, and download link
        successAlert.style.display = 'none';
        errorAlert.style.display = 'none';
        resultText.style.display = 'none';
        resultText.innerHTML = '';
        downloadLink.style.display = 'none';
        downloadLink.innerHTML = '';

        // Show image progress bar
        imageProgressWrapper.style.display = 'block';

        let xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);

        xhr.upload.addEventListener('progress', function(event) {
            if (event.lengthComputable) {
                let percentComplete = Math.round((event.loaded / event.total) * 100);
                imageProgressBar.value = percentComplete;
                imageProgressPercent.textContent = percentComplete + '%';
            }
        });

        xhr.addEventListener('load', function(event) {
            if (xhr.status === 200) {
                successAlert.style.display = 'block';
                let response = JSON.parse(xhr.responseText);
                if (response.text_file_url) {
                    resultText.style.display = 'block';
                    resultText.innerHTML = '<h4>Conversion Complete!</h4>';
                    downloadLink.style.display = 'block';
                    downloadLink.innerHTML = '<a href="' + response.text_file_url + '" download>Download Converted Text File</a>';
                }
            } else {
                errorAlert.style.display = 'block';
            }
            imageProgressWrapper.style.display = 'none';
            textProgressWrapper.style.display = 'none';
            imageProgressBar.value = 0;
            textProgressBar.value = 0;
            imageProgressPercent.textContent = '0%';
            textProgressPercent.textContent = '0%';
        });

        xhr.addEventListener('error', function(event) {
            errorAlert.style.display = 'block';
            imageProgressWrapper.style.display = 'none';
            textProgressWrapper.style.display = 'none';
            imageProgressBar.value = 0;
            textProgressBar.value = 0;
            imageProgressPercent.textContent = '0%';
            textProgressPercent.textContent = '0%';
        });

        xhr.send(formData);

        // Start polling for progress updates
        pollProgress('image_conversion', imageProgressBar, imageProgressPercent, imageProgressWrapper, textProgressWrapper);
        pollProgress('text_conversion', textProgressBar, textProgressPercent, textProgressWrapper);
    });

    function pollProgress(type, progressBar, progressPercent, progressWrapperToHide, progressWrapperToShow) {
        setInterval(function() {
            fetch('/get-progress?type=' + type)
                .then(response => response.json())
                .then(data => {
                    if (data.progress) {
                        progressBar.value = data.progress;
                        progressPercent.textContent = data.progress + '%';

                        if (data.progress === 100 && progressWrapperToShow) {
                            progressWrapperToHide.style.display = 'none';
                            progressWrapperToShow.style.display = 'block';
                        }
                    }
                });
        }, 1000); // Poll every second
    }
</script>
