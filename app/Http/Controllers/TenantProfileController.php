<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TenantProfileController extends Controller
{
    // /**
    //  * Get tenant profile information.
    //  */
    // public function show(): JsonResponse
    // {
    //     try {
    //         $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();

    //         return response()->json([
    //             'success' => true,
    //             'profile' => [
    //                 'business_name' => $tenant->business_name,
    //                 'business_address' => $tenant->business_address,
    //                 'business_city' => $tenant->business_city,
    //                 'business_state' => $tenant->business_state,
    //                 'business_postal_code' => $tenant->business_postal_code,
    //                 'business_country' => $tenant->business_country,
    //                 'business_phone' => $tenant->business_phone,
    //                 'business_email' => $tenant->business_email,
    //                 'business_description' => $tenant->business_description,
    //                 'business_logo_url' => $tenant->business_logo_url,
    //             ],
    //             'completion' => [
    //                 'is_complete' => $tenant->isProfileComplete(),
    //                 'percentage' => $tenant->getProfileCompletionPercentage(),
    //                 'completed_at' => $tenant->profile_completed_at,
    //                 'steps' => $tenant->profile_completion_steps ?? [],
    //             ],
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to fetch tenant profile: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch profile',
    //         ], 500);
    //     }
    // }
public function show(): JsonResponse
{
    $tenant = Tenant::where('user_id', Auth::id())->first();

    // if (!$tenant) {
    //     return response()->json([
    //         'success' => false,
    //         'code' => 'NO_TENANT',
    //         'message' => 'No salon created yet',
    //     ], 404);
    // }
if (!$tenant) {
    return response()->json([
        'success' => true,
        'profile' => null,
        'completion' => [
            'is_complete' => false,
            'percentage' => 0,
            'completed_at' => null,
            'steps' => [],
        ],
    ]);
}

    return response()->json([
        'success' => true,
        'profile' => [
            'business_name' => $tenant->business_name,
            'business_address' => $tenant->business_address,
            'business_city' => $tenant->business_city,
            'business_state' => $tenant->business_state,
            'business_postal_code' => $tenant->business_postal_code,
            'business_country' => $tenant->business_country,
            'business_phone' => $tenant->business_phone,
            'business_email' => $tenant->business_email,
            'business_description' => $tenant->business_description,
            'business_logo_url' => $tenant->business_logo_url,
        ],
        'completion' => [
            'is_complete' => $tenant->isProfileComplete(),
            'percentage' => $tenant->getProfileCompletionPercentage(),
            'completed_at' => $tenant->profile_completed_at,
            'steps' => $tenant->profile_completion_steps ?? [],
        ],
    ]);
}

    /**
     * Update tenant profile information.
     */
    public function update(Request $request): JsonResponse
    {
        Log::info('FILES RECEIVED:', $request->allFiles());
if ($request->hasFile('business_logo')) {
    $path = $request->file('business_logo')->store('logos', 'public');
    $updateData['business_logo_url'] = '/storage/' . $path;
    Log::info('LOGO STORED AT: ' . $updateData['business_logo_url']);
}


        $validator = Validator::make($request->all(), [
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string|max:255',
            'business_city' => 'nullable|string|max:100',
            'business_state' => 'nullable|string|max:100',
            'business_postal_code' => 'nullable|string|max:20',
            'business_country' => 'nullable|string|max:100',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email|max:255',
            'business_description' => 'nullable|string|max:1000',
            'business_logo' => 'nullable|file|mimes:png|max:1024',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();
            
            $updateData = [];
            $steps = $tenant->profile_completion_steps ?? [];

            if ($request->has('business_name') && $request->business_name) {
                $updateData['business_name'] = $request->business_name;
                $steps['business_name'] = true;
            }

            if ($request->has('business_address') && $request->business_address) {
                $updateData['business_address'] = $request->business_address;
                $steps['business_address'] = true;
            }

            if ($request->has('business_phone') && $request->business_phone) {
                $updateData['business_phone'] = $request->business_phone;
                $steps['business_phone'] = true;
            }

            if ($request->has('business_email') && $request->business_email) {
                $updateData['business_email'] = $request->business_email;
                $steps['business_email'] = true;
            }

            if ($request->has('business_description') && $request->business_description) {
                $updateData['business_description'] = $request->business_description;
                $steps['business_description'] = true;
            }

            if ($request->has('business_city')) {
                $updateData['business_city'] = $request->business_city;
            }
            if ($request->has('business_state')) {
                $updateData['business_state'] = $request->business_state;
            }
            if ($request->has('business_postal_code')) {
                $updateData['business_postal_code'] = $request->business_postal_code;
            }
            if ($request->has('business_country')) {
                $updateData['business_country'] = $request->business_country;
            }
           // Logo upload handling
if ($request->hasFile('business_logo')) {
    try {
        $updateData['business_logo_url'] = $this->uploadLogo($request, $tenant);
        $steps['business_logo'] = true;
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}


            $completedRequired = count(array_filter($steps, function($v) { return $v === true; }));
            $isComplete = $completedRequired >= 5;

            $updateData['profile_completion_steps'] = $steps;
            if ($isComplete && !$tenant->profile_completed) {
                $updateData['profile_completed'] = true;
                $updateData['profile_completed_at'] = now();
            }

            $tenant->update($updateData);

            Log::info("Tenant profile updated for user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'profile' => [
                    'business_name' => $tenant->business_name,
                    'business_address' => $tenant->business_address,
                    'business_city' => $tenant->business_city,
                    'business_state' => $tenant->business_state,
                    'business_postal_code' => $tenant->business_postal_code,
                    'business_country' => $tenant->business_country,
                    'business_phone' => $tenant->business_phone,
                    'business_email' => $tenant->business_email,
                    'business_description' => $tenant->business_description,
                    'business_logo_url' => $tenant->business_logo_url,
                ],
                'completion' => [
                    'is_complete' => $tenant->isProfileComplete(),
                    'percentage' => $tenant->getProfileCompletionPercentage(),
                    'completed_at' => $tenant->profile_completed_at,
                    'steps' => $tenant->profile_completion_steps ?? [],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update tenant profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
            ], 500);
        }
    }

    /**
     * Get profile completion status.
     */
    public function getCompletionStatus(): JsonResponse
    {
        try {
            $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();

            $requiredFields = [
                'business_name' => $tenant->business_name ? true : false,
                'business_address' => $tenant->business_address ? true : false,
                'business_phone' => $tenant->business_phone ? true : false,
                'business_email' => $tenant->business_email ? true : false,
                'business_description' => $tenant->business_description ? true : false,
            ];

            $completedCount = count(array_filter($requiredFields, function($v) { return $v === true; }));
            $totalRequired = count($requiredFields);

            return response()->json([
                'success' => true,
                'is_complete' => $tenant->isProfileComplete(),
                'percentage' => $tenant->getProfileCompletionPercentage(),
                'required_fields' => $requiredFields,
                'completed_count' => $completedCount,
                'total_required' => $totalRequired,
                'missing_fields' => array_keys(array_filter($requiredFields, function($v) { return $v === false; })),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get completion status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get completion status',
            ], 500);
        }
    }
private function uploadLogo(Request $request, $tenant)
{
    $file = $request->file('business_logo');

    $path = $file->store('logos', 'public');

    // نرجع الرابط النهائي الذي يجب تخزينه في قاعدة البيانات
    return '/storage/' . $path;
}



}
