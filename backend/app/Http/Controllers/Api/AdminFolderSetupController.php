<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AdminFolderSetupController extends Controller
{
    /**
     * Setup or update the base HR folder structure.
     * Only admin should call this API.
     */
    public function setupStructure(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }
        $basePath = "company/hr-documents";

        // Default structure
        $defaultStructure = [
            'personal',
            'employment',
            'finance',
            'performance',
        ];

        // Optional custom folders from request
        $customFolders = $request->input('folders', []);

        $finalStructure = array_unique(array_merge($defaultStructure, $customFolders));

        try {
            foreach ($finalStructure as $folderName) {
                $folderPath = "{$basePath}/{$folderName}";

                // Check if folder already exists (avoid overwrite)
                if (!Storage::disk('s3')->exists($folderPath)) {
                    Storage::disk('s3')->makeDirectory($folderPath);

                    // Save record in DB
                    Folder::firstOrCreate([
                        'name' => ucfirst($folderName),
                        'user_id' => 1, // Admin-level
                        's3_path' => $folderPath,
                        'parent_id' => null,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Base HR folder structure verified or created successfully.',
                'folders' => $finalStructure,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Failed to setup folder structure.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a new HR folder or subfolder (without affecting existing).
     */
    public function addFolder(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }
        $request->validate([
            'name' => 'required|string|max:100',
            'parent' => 'nullable|string',
        ]);

        $basePath = "company/hr-documents";

        $parentPath = $request->parent ? "{$basePath}/{$request->parent}" : $basePath;
        $newPath = "{$parentPath}/{$request->name}";

        try {
            if (Storage::disk('s3')->exists($newPath)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Folder already exists.',
                ], 409);
            }

            Storage::disk('s3')->makeDirectory($newPath);

            Folder::firstOrCreate([
                'name' => ucfirst($request->name),
                'user_id' => 1,
                's3_path' => $newPath,
                'parent_id' => null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'New folder created successfully.',
                'path' => $newPath,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Failed to create new folder.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
