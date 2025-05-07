<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Organization;
use App\Models\ServiceFees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationController extends BaseController
{
  /**
   * Get all organizations with their related services
   */
  public function getOrganizations()
  {
    try {
      $organizations = Organization::with('services')->get();
      return $this->sendResponse(['organizations' => $organizations], 'List of organizations retrieved');
    } catch (\Exception $e) {
      \Log::error('Error fetching organizations: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Get a specific organization by ID with its services
   */
  public function getOrganization($id)
  {
    try {
      $organization = Organization::with('services')->findOrFail($id);
      return $this->sendResponse(['organization' => $organization], 'Organization retrieved');
    } catch (\Exception $e) {
      \Log::error('Error fetching organization: ' . $e->getMessage());
      return $this->sendError('Organization not found', ['error' => $e->getMessage()], 404);
    }
  }

  /**
   * Create a new organization with at least one service
   */
  public function createOrganization(Request $request)
  {
    DB::beginTransaction();
    try {
      // Validate organization details
      $validated = $request->validate([
        'name' => 'required|string',
        'type' => 'required|string',
        // Require at least one service
        'services' => 'required|array|min:1',
        'services.*.name' => 'required|string',
        'services.*.type' => 'required|string',
        'services.*.state' => 'required|string',
        'services.*.amount' => 'required|numeric',
        'services.*.description' => 'nullable|string',
        'services.*.status' => 'boolean',
        'services.*.metadata' => 'nullable|array',
        'services.*.metadata.payment_support' => 'nullable|array',
        'services.*.metadata.payment_type' => 'nullable|string',
      ]);

      // Create the organization
      $organizationData = [
        'id' => Str::ulid(),
        'name' => $validated['name'],
        'type' => $validated['type']
      ];
      $organization = Organization::create($organizationData);

      // Create associated services
      $services = [];
      foreach ($validated['services'] as $serviceData) {
        $service = new ServiceFees([
          'id' => Str::ulid(),
          'name' => $serviceData['name'],
          'type' => $serviceData['type'],
          'state' => $serviceData['state'],
          'amount' => $serviceData['amount'],
          'status' => $serviceData['status'] ?? true,
          'description' => $serviceData['description'] ?? null,
          'metadata' => $serviceData['metadata'] ?? null
        ]);

        // Associate with organization and save
        $organization->services()->save($service);
        $services[] = $service;
      }

      DB::commit();

      // Return the created organization with its services
      $organization->load('services');
      return $this->sendResponse(['organization' => $organization], 'Organization created successfully with services', 201);
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error('Organization creation error: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Update an existing organization
   */
  public function updateOrganization(Request $request, $id)
  {
    DB::beginTransaction();
    try {
      $validated = $request->validate([
        'name' => 'nullable|string',
        'type' => 'nullable|string',
      ]);

      $organization = Organization::findOrFail($id);
      $organization->update($validated);

      DB::commit();
      return $this->sendResponse(['organization' => $organization], 'Organization updated successfully');
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error('Organization update error: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Delete an organization and its associated services
   */
  public function deleteOrganization($id)
  {
    DB::beginTransaction();
    try {
      $organization = Organization::findOrFail($id);

      // The services will automatically be deleted due to cascadeOnDelete() in migration
      $organization->delete();

      DB::commit();
      return $this->sendResponse([], 'Organization and its services deleted successfully');
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error('Organization deletion error: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }
}
