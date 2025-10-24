<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;

class DocumentController extends Controller
{
    /**
     * Employee uploads a document into existing HR folder
     */
    public function upload(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'file' => 'required|file|max:10240',
        ]);

        $user = Auth::user();
        $category = strtolower($request->category);
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        $filename = "emp_{$user->id}_" . pathinfo($originalName, PATHINFO_FILENAME) . '.' . $extension;
        $path = "company/hr-documents/{$category}/{$filename}";

        try {
            $existingDoc = Document::where('user_id', $user->id)
                ->where('folder_name', $category)
                ->where('file_name', $filename)
                ->first();

            if ($existingDoc) {
                return response()->json([
                    'status' => false,
                    'message' => 'This document already exists.',
                ], 409);
            }

            if (Storage::disk('s3')->exists($path)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File already exists on S3.',
                ], 409);
            }

            Storage::disk('s3')->put($path, file_get_contents($file));

            Document::create([
                'user_id' => $user->id,
                'folder_name' => $category,
                'file_name' => $filename,
                's3_path' => $path,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Document uploaded successfully.',
                'file_url' => Storage::disk('s3')->url($path),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Upload failed.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all employee documents by folder
     */
    public function listByFolder($folder)
    {
        $user = Auth::user();

        $docs = Document::where('user_id', $user->id)
            ->where('folder_name', $folder)
            ->get();

        return response()->json([
            'status' => true,
            'documents' => $docs,
        ]);
    }

    /**
     * Delete a specific document
     */
    public function delete($category, $id)
    {
        try {
            // Validate category
            $allowedCategories = ['employment', 'profile', 'personal'];
            if (!in_array($category, $allowedCategories)) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid folder category.',
                ], 400);
            }

            // Find document in DB
            $document = Document::find($id);
            if (!$document) {
                return response()->json([
                    'status' => false,
                    'error' => 'Document not found.',
                ], 404);
            }

            // Build S3 full path
            $path = "company/hr-documents/{$category}/{$document->file_name}";

            // Delete from S3 if exists
            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
            }

            // Delete from database
            $document->delete();

            return response()->json([
                'status' => true,
                'message' => "Document deleted successfully from '{$category}' folder.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Failed to delete document.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
