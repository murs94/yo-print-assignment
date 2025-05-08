<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .upload-container {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        #uploadForm {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        #uploadList {
            list-style: none;
            padding: 0;
        }
        #uploadList li {
            margin-bottom: 10px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        button {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        input[type="file"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex-grow: 1;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>File Upload</h1>
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit">Upload</button>
        </form>
    </div>

    <h2>Recent Uploads</h2>
    <ul id="uploadList"></ul>

    <script>
        $(document).ready(function() {
            function getStatusClass(status) {
                return 'status status-' + status.toLowerCase();
            }

            function formatDate(dateString) {
                return new Date(dateString).toLocaleString();
            }

            function loadUploads() {
                $.get('/uploads', function(data) {
                    $('#uploadList').empty();
                    data.forEach(function(upload) {
                        $('#uploadList').append(`
                            <li>
                                <div>
                                    <strong>${upload.filename}</strong>
                                    <div>Uploaded: ${formatDate(upload.uploaded_at)}</div>
                                </div>
                                <span class="${getStatusClass(upload.status)}">${upload.status}</span>
                            </li>
                        `);
                    });
                });
            }

            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                var submitButton = $(this).find('button[type="submit"]');

                submitButton.prop('disabled', true).text('Uploading...');

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
                    error: function(xhr) {
                        alert('Upload failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).text('Upload');
                        $('#uploadForm')[0].reset();
                    }
                });
            });

            loadUploads();
            setInterval(loadUploads, 2000); // Poll every 2 seconds for more real-time updates
        });
    </script>
</body>
</html>
