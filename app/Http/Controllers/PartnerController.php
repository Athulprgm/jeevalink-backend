<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Partner;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PartnerController extends Controller
{
    /**
     * Get all partners.
     * Public access.
     */
    public function index()
    {
        $partners = Partner::orderBy('created_at', 'desc')->get()->map(function ($p) {
            $p->_id = (string)$p->id;
            return $p;
        });

        return response()->json([
            'success' => true,
            'message' => 'Partners retrieved successfully.',
            'data'    => ['partners' => $partners]
        ]);
    }

    /**
     * Add a new partner.
     * Admin only.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'logo'                => 'required', // can be file or string url
            'social_media_type'   => 'required|string|max:50',
            'social_media_link'   => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $logoPath = '';
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('partners', 'public');
            $logoPath = '/storage/' . $path;
        } else {
            $logoPath = $request->logo;
        }

        $partner = Partner::create([
            'name'              => $request->name,
            'logo'              => $logoPath,
            'social_media_type' => strtolower($request->social_media_type),
            'social_media_link' => $request->social_media_link,
        ]);

        $partner->_id = (string)$partner->id;

        return response()->json([
            'success' => true,
            'message' => 'Partner added successfully.',
            'data'    => ['partner' => $partner]
        ], 201);
    }

    /**
     * Update a partner.
     * Admin only.
     */
    public function update(Request $request, $id)
    {
        $partner = Partner::find($id);
        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'                => 'sometimes|required|string|max:255',
            'social_media_type'   => 'sometimes|required|string|max:50',
            'social_media_link'   => 'sometimes|required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        if ($request->has('name')) $partner->name = $request->name;
        if ($request->has('social_media_type')) $partner->social_media_type = strtolower($request->social_media_type);
        if ($request->has('social_media_link')) $partner->social_media_link = $request->social_media_link;

        if ($request->hasFile('logo')) {
            // Delete old file if exists
            if ($partner->logo) {
                if (str_starts_with($partner->logo, '/storage/')) {
                    $oldPath = str_replace('/storage/', '', $partner->logo);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                } elseif (str_starts_with($partner->logo, '/uploads/')) {
                    $oldPath = public_path(ltrim($partner->logo, '/'));
                    if (File::exists($oldPath)) {
                        File::delete($oldPath);
                    }
                }
            }

            $path = $request->file('logo')->store('partners', 'public');
            $partner->logo = '/storage/' . $path;
        } elseif ($request->has('logo') && is_string($request->logo)) {
            $partner->logo = $request->logo;
        }

        $partner->save();
        $partner->_id = (string)$partner->id;

        return response()->json([
            'success' => true,
            'message' => 'Partner updated successfully.',
            'data'    => ['partner' => $partner]
        ]);
    }

    /**
     * Delete a partner.
     * Admin only.
     */
    public function destroy($id)
    {
        $partner = Partner::find($id);
        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found.'
            ], 404);
        }

        // Delete logo file from storage
        if ($partner->logo) {
            if (str_starts_with($partner->logo, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $partner->logo);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            } elseif (str_starts_with($partner->logo, '/uploads/')) {
                $filePath = public_path(ltrim($partner->logo, '/'));
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }
        }

        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partner deleted successfully.'
        ]);
    }
}
