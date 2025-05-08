<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Jobs\ProcessFileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Create upload record with pending status
            $upload = Upload::create([
                'filename' => $file->getClientOriginalName(),
                'path' => '',
                'status' => 'pending',
                'uploaded_at' => now(),
            ]);

            try {
                // Store the file
                $path = $file->store('uploads');

                // Update the upload record with path
                $upload->update(['path' => $path]);

                // Dispatch the job to process the file
                ProcessFileUpload::dispatch($upload);

                return response()->json([
                    'status' => 'success',
                    'upload' => $this->transformUpload($upload)
                ]);
            } catch (\Exception $e) {
                $upload->update(['status' => 'failed']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Upload failed: ' . $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['status' => 'error', 'message' => 'No file uploaded'], 400);
    }

    public function index()
    {
        $uploads = Upload::orderBy('uploaded_at', 'desc')->get();
        return response()->json($uploads->map(fn($upload) => $this->transformUpload($upload)));
    }

    protected function transformUpload(Upload $upload)
    {
        return [
            'id' => $upload->id,
            'filename' => $upload->filename,
            'status' => $upload->status,
            'uploaded_at' => $upload->uploaded_at->format('Y-m-d H:i:s'),
            'created_at' => $upload->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
