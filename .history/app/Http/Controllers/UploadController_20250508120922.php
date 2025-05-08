<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('uploads');
            return response()->json(['status' => 'success', 'path' => $path]);
        }
        return response()->json(['status' => 'error', 'message' => 'No file uploaded'], 400);
    }
}
