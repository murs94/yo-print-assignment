<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #uploadForm { margin-bottom: 20px; }
        #uploadList { list-style: none; padding: 0; }
        #uploadList li { margin-bottom: 10px; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>File Upload</h1>
    <form id="uploadForm" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>
    <h2>Recent Uploads</h2>
    <ul id="uploadList"></ul>

    <script>
        $(document).ready(function() {
            function loadUploads() {
                $.get('/uploads', function(data) {
                    $('#uploadList').empty();
                    data.forEach(function(upload) {
                        $('#uploadList').append('<li>File: ' + upload.filename + ' - Uploaded at: ' + upload.uploaded_at + '</li>');
                    });
                });
            }

            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: '/upload',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        alert('Upload successful!');
                        loadUploads();
                    },
                    error: function() {
                        alert('Upload failed!');
                    }
                });
            });

            loadUploads();
            setInterval(loadUploads, 5000); // Poll every 5 seconds
        });
    </script>
</body>
</html>
