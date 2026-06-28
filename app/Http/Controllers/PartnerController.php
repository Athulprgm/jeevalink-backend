<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Partner;
use Illuminate\Support\Facades\Validator;

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
     * Logo must be a publicly accessible URL (file upload not supported on Railway).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'              => 'required|string|max:255',
            'logo'              => 'required|string|max:1000',
            'social_media_type' => 'required|string|max:50',
            'social_media_link' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $partner = Partner::create([
            'name'              => $request->name,
            'logo'              => $request->logo,
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
            'name'              => 'sometimes|required|string|max:255',
            'logo'              => 'sometimes|required|string|max:1000',
            'social_media_type' => 'sometimes|required|string|max:50',
            'social_media_link' => 'sometimes|required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        if ($request->filled('name'))              $partner->name              = $request->name;
        if ($request->filled('logo'))              $partner->logo              = $request->logo;
        if ($request->filled('social_media_type')) $partner->social_media_type = strtolower($request->social_media_type);
        if ($request->filled('social_media_link')) $partner->social_media_link = $request->social_media_link;

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

        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partner deleted successfully.'
        ]);
    }
}
