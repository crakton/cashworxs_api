<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Organization;
use App\Models\ServiceFees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceFeesController extends BaseController
{
  /**
   * Get all organizations with their related services
   */
  public function getServiceFees()
  {
    try {
      $organizations = Organization::with('services')->get();
      return $this->sendResponse(['fees' => $organizations], 'List of services supported');
    } catch (\Exception $e) {
      \Log::error('Error fetching service fees: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Get a specific service fee by ID
   */
  public function getServiceFee($id)
  {
    try {
      $fee = ServiceFees::with('organization')->findOrFail($id);
      return $this->sendResponse(['fee' => $fee], 'Service fee retrieved');
    } catch (\Exception $e) {
      \Log::error('Error fetching service fee: ' . $e->getMessage());
      return $this->sendError('Service fee not found', ['error' => $e->getMessage()], 404);
    }
  }

  /**
   * Get all services
   */
  public function getServices()
  {
    try {
      \Log::info('getServices method was called');
      $services = ServiceFees::with('organization')->get();
      return $this->sendResponse(['services' => $services], 'List of services');
    } catch (\Exception $e) {
      \Log::error('Error fetching services: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Create a new service fee and associate it with an organization
   */
  public function createServiceFee(Request $request)
  {
    DB::beginTransaction();
    try {
      $validated = $request->validate([
        'name' => 'required|string',
        'type' => 'required|string',
        'state' => 'required|string',
        'amount' => 'required|numeric',
        'description' => 'nullable|string',
        'status' => 'boolean',
        'organization_id' => 'required|string|exists:organizations,id',
        'metadata' => 'nullable|array',
        'metadata.payment_support' => 'nullable|array',
        'metadata.payment_type' => 'nullable|string',
      ]);

      // Find the organization
      $organization = Organization::findOrFail($validated['organization_id']);

      // Create the service fee with organization_id
      $fee = ServiceFees::create($validated);

      DB::commit();
      return $this->sendResponse(['fee' => $fee], 'Service fee created successfully', 201);
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error('Service fee creation error: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Update an existing service fee
   */
  public function updateServiceFee(Request $request, $id)
  {
    DB::beginTransaction();
    try {
      $validated = $request->validate([
        'name' => 'nullable|string',
        'type' => 'nullable|string',
        'state' => 'nullable|string',
        'amount' => 'nullable|numeric',
        'description' => 'nullable|string',
        'status' => 'nullable|boolean',
        'organization_id' => 'nullable|string|exists:organizations,id',
        'metadata' => 'nullable|array',
        'metadata.payment_support' => 'nullable|array',
        'metadata.payment_type' => 'nullable|string',
      ]);

      $fee = ServiceFees::findOrFail($id);
      $fee->update($validated);

      DB::commit();
      return $this->sendResponse(['fee' => $fee], 'Service fee updated successfully');
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error('Service fee update error: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Delete a service fee
   */
  public function deleteServiceFee($id)
  {
    DB::beginTransaction();
    try {
      $fee = ServiceFees::findOrFail($id);
      $fee->delete();

      DB::commit();
      return $this->sendResponse([], 'Service fee deleted successfully');
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error('Service fee deletion error: ' . $e->getMessage());
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }
}
