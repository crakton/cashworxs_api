<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Fee;
use App\Models\Tax;
use App\Models\ServiceFees;
use App\Models\ServiceTaxes;
use App\Models\Organization;

class AdminDashboardController extends BaseController
{
    public function getDashboardStats()
    {
        try {
            return $this->sendResponse([
                'total_users' => User::count(),
                'total_fees' => Fee::count(),
                'total_taxes' => Tax::count(),
                'total_service_fees' => ServiceFees::count(),
                'total_service_taxes' => ServiceTaxes::count(),
                'recent_transactions' => Fee::with('user')->latest()->take(5)->get(),
                'recent_users' => User::latest()->take(5)->get()
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function getOrganizations()
    {
        try {
            $organizations = Organization::all();
            return $this->sendResponse(['organizations' => $organizations], 'List of organizations');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    // create organization
    public function createOrganization(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'status' => 'boolean',
                'metadata' => 'nullable|array',
            ]);

            $organization = Organization::create($validated);

            return $this->sendResponse(['organization' => $organization], 'Organization created successfully', 201);
        } catch (\Exception $e) {
            \Log::error('Organization creation error: ' . $e->getMessage());
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
    // update organization
    public function updateOrganization(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string',
                'description' => 'nullable|string',
                'status' => 'boolean',
                'metadata' => 'nullable|array',
            ]);

            $organization = Organization::findOrFail($id);
            $organization->update($validated);

            return $this->sendResponse(['organization' => $organization], 'Organization updated successfully');
        } catch (\Exception $e) {
            \Log::error('Organization update error: ' . $e->getMessage());
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
    // delete organization
    public function deleteOrganization($id)
    {
        try {
            $organization = Organization::findOrFail($id);
            $organization->delete();

            return $this->sendResponse([], 'Organization deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Organization deletion error: ' . $e->getMessage());
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
    // get organization by id
    public function getOrganizationById($id)
    {
        try {
            $organization = Organization::findOrFail($id);
            return $this->sendResponse(['organization' => $organization], 'Organization details');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
