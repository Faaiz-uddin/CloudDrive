<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FolderController extends Controller
{
    /**
     * Generate predefined HR folder structure for an employee.
     */
    public function setupDefaultStructure(Request $request)
    {
        $user = Auth::user();
        $basePath = "users/{$user->id}/hr-documents";

        $structure = [
            'personal' => ['CNIC', 'Passport', 'Profile_Photo'],
            'employment' => ['Offer_Letter', 'Appointment_Letter', 'Experience_Certificate'],
            'finance' => ['Salary_Slips', 'Tax_Documents', 'Provident_Fund'],
            'performance' => ['Appraisals', 'Warning_Letters'],
        ];

        try {
            foreach ($structure as $main => $subFolders) {
                $mainPath = "{$basePath}/{$main}";
                Storage::disk('s3')->makeDirectory($mainPath);

                $mainFolder = Folder::create([
                    'name' => ucfirst($main),
                    'user_id' => $user->id,
                    's3_path' => $mainPath,
                    'parent_id' => null,
                ]);

                foreach ($subFolders as $sub) {
                    $subPath = "{$mainPath}/{$sub}";
                    Storage::disk('s3')->makeDirectory($subPath);

                    Folder::create([
                        'name' => $sub,
                        'user_id' => $user->id,
                        's3_path' => $subPath,
                        'parent_id' => $mainFolder->id,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'HR document folder structure created successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create HR folder structure.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List employee’s HR document folders.
     */
    public function index()
    {
        $folders = Folder::where('user_id', Auth::id())
            ->whereNull('parent_id')
            ->with('children')
            ->get();

        return response()->json([
            'status' => true,
            'folders' => $folders,
        ]);
    }

    /**
     * Delete the complete HR structure (for example, when employee leaves).
     */
    public function destroyAll()
    {
        $user = Auth::user();
        $basePath = "users/{$user->id}/hr-documents";

        try {
            Storage::disk('s3')->deleteDirectory($basePath);
            Folder::where('user_id', $user->id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'All HR folders deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete HR folders.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
