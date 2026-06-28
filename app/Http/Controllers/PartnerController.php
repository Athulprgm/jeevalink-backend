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
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'              => 'required|string|max:255',
            'logo'              => 'required', // Can be file or string
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

        $logoData = '';
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $mime = $file->getClientMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $logoData = 'data:' . $mime . ';base64,' . $base64;
        } else {
            $logoData = $request->logo;
        }

        $partner = Partner::create([
            'name'              => $request->name,
            'logo'              => $logoData,
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

        // We don't make logo required for update, it can be 'sometimes'
        $validator = Validator::make($request->all(), [
            'name'              => 'sometimes|required|string|max:255',
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

        if ($request->has('name') || $request->filled('name')) {
            $partner->name = $request->name;
        }
        if ($request->has('social_media_type') || $request->filled('social_media_type')) {
            $partner->social_media_type = strtolower($request->social_media_type);
        }
        if ($request->has('social_media_link') || $request->filled('social_media_link')) {
            $partner->social_media_link = $request->social_media_link;
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $mime = $file->getClientMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $partner->logo = 'data:' . $mime . ';base64,' . $base64;
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

        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partner deleted successfully.'
        ]);
    }
}
