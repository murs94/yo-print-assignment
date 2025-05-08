<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 32px 32px 24px 32px;
        }
        .drop-area {
            border: 2px dashed #bbb;
            border-radius: 8px;
            padding: 32px;
            text-align: center;
            color: #888;
            margin-bottom: 16px;
            background: #f5f5f5;
            transition: border-color 0.2s;
        }
        .drop-area.dragover {
            border-color: #007bff;
            color: #007bff;
        }
        #uploadForm {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
        }
        #uploadForm input[type="file"] {
            display: none;
        }
        #uploadBtn {
            padding: 8px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        #uploadBtn:disabled {
            background: #aaa;
            cursor: not-allowed;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background: #f1f1f1;
            font-weight: bold;
        }
        .status {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.95em;
            text-transform: capitalize;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .time-secondary {
            color: #888;
            font-size: 0.95em;
        }
    </style>
</head>
<body>
<div class="container">
    <form id="uploadForm" enctype="multipart/form-data">
        @csrf
        <input type="file" id="fileInput" name="file" required>
        <button type="button" id="uploadBtn">Upload File</button>
    </form>
    <div class="drop-area" id="dropArea">Select file/Drag and drop</div>
    <table>
        <thead>
        <tr>
            <th>Time</th>
            <th>File Name</th>
            <th>Status</th>
            <th>Progress</th>
        </tr>
        </thead>
        <tbody id="uploadTableBody"></tbody>
    </table>
</div>
<script>
    $(document).ready(function() {
        // CSRF setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Drag and drop logic
        let fileToUpload = null;
        const dropArea = $('#dropArea');
        const fileInput = $('#fileInput');
        const uploadBtn = $('#uploadBtn');

        dropArea.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropArea.addClass('dragover');
        });
        dropArea.on('dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropArea.removeClass('dragover');
        });
        dropArea.on('drop', function(e) {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                fileToUpload = files[0];
                dropArea.text(fileToUpload.name);
            }
        });
        dropArea.on('click', function() {
            fileInput.click();
        });
        fileInput.on('change', function(e) {
            if (e.target.files.length > 0) {
                fileToUpload = e.target.files[0];
                dropArea.text(fileToUpload.name);
            }
        });
        uploadBtn.on('click', function() {
            if (!fileToUpload) {
                alert('Please select a file first.');
                return;
            }
            let formData = new FormData();
            formData.append('file', fileToUpload);
            uploadBtn.prop('disabled', true).text('Uploading...');
            $("#frontendProgress").remove();
            dropArea.after('<div id="frontendProgress" style="margin:10px 0;"><div style="background:#e9ecef;border-radius:4px;height:18px;width:100%;overflow:hidden;"><div id="frontendBar" style="background:#007bff;height:100%;width:0%;transition:width 0.2s;"></div></div><span id="frontendPercent">0%</span></div>');
            $.ajax({
                url: '/upload',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            var percent = Math.round((evt.loaded / evt.total) * 100);
                            $('#frontendBar').css('width', percent + '%');
                            $('#frontendPercent').text(percent + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    alert('Upload successful!');
                    dropArea.text('Select file/Drag and drop');
                    fileToUpload = null;
                    fileInput.val('');
                    loadUploads();
                },
                error: function(xhr) {
                    alert('Upload failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
                },
                complete: function() {
                    uploadBtn.prop('disabled', false).text('Upload File');
                    setTimeout(function() { $("#frontendProgress").fadeOut(500, function() { $(this).remove(); }); }, 1500);
                }
            });
        });

        function getStatusClass(status) {
            return 'status status-' + status.toLowerCase();
        }
        function formatDate(dateString) {
            const d = new Date(dateString.replace(' ', 'T'));
            return d.toLocaleString();
        }
        function timeAgo(dateString) {
            const now = new Date();
            const then = new Date(dateString.replace(' ', 'T'));
            const diff = Math.floor((now - then) / 1000);
            if (diff < 60) return `${diff} seconds ago`;
            if (diff < 3600) return `${Math.floor(diff/60)} minutes ago`;
            if (diff < 86400) return `${Math.floor(diff/3600)} hours ago`;
            return `${Math.floor(diff/86400)} days ago`;
        }
        function loadUploads() {
            $.get('/uploads', function(data) {
                $('#uploadTableBody').empty();
                data.forEach(function(upload) {
                    $('#uploadTableBody').append(`
                        <tr>
                            <td>
                                ${formatDate(upload.uploaded_at)}<br>
                                <span class="time-secondary">(${timeAgo(upload.uploaded_at)})</span>
                            </td>
                            <td>${upload.filename}</td>
                            <td><span class="${getStatusClass(upload.status)}">${upload.status}</span></td>
                            <td>
                                <div style="background:#e9ecef;border-radius:4px;height:16px;width:120px;overflow:hidden;">
                                    <div style="background:#28a745;height:100%;width:${upload.progress || 0}%;transition:width 0.2s;"></div>
                                </div>
                                <span style="font-size:0.95em;">${upload.progress || 0}%</span>
                            </td>
                        </tr>
                    `);
                });
            });
        }
        loadUploads();
        setInterval(loadUploads, 2000);
    });
</script>
</body>
</html>
