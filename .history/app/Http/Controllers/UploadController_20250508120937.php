<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('uploads');
            $upload = Upload::create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'uploaded_at' => now(),
            ]);
            return response()->json(['status' => 'success', 'path' => $path, 'upload' => $upload]);
        }
        return response()->json(['status' => 'error', 'message' => 'No file uploaded'], 400);
    }
}
