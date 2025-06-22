<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreIRSRequest;
use App\Http\Requests\UpdateIRSRequest;
use App\Http\Resources\InternalRevenueServiceResource;
use App\Models\InternalRevenueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InternalRevenueServiceController extends BaseController
{
    /**
     * Display a listing of IRS
     */
    public function index(Request $request)
    {
        try {
           
            $query = InternalRevenueService::with(['state']);
            
            if ($request->has('active_only') && $request->active_only) {
                $query->active();
            }
            
            if ($request->has('state_id')) {
                $query->where('state_id', $request->state_id);
            }
            
            $irsList = $query->orderBy('irs_name')->get();
            
            return $this->sendResponse(
                InternalRevenueServiceResource::collection($irsList),
                'Internal Revenue Services retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error retrieving Internal Revenue Services: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created IRS
     */
    public function store(StoreIRSRequest $request)
    {
        try {
            $this->authorize('create', InternalRevenueService::class);

            $irs = InternalRevenueService::create($request->validated());

            return $this->sendResponse(
                new InternalRevenueServiceResource($irs->load('state')),
                'Internal Revenue Service created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error creating Internal Revenue Service: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified IRS
     */
    public function show(Request $request, $id)
    {
        try {
            $irs = InternalRevenueService::with(['state'])->findOrFail($id);

            return $this->sendResponse(
                new InternalRevenueServiceResource($irs),
                'Internal Revenue Service retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error retrieving Internal Revenue Service: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified IRS
     */
    public function update(UpdateIRSRequest $request, $id): JsonResponse
    {
        try {
            $irs = InternalRevenueService::findOrFail($id);
            $this->authorize('update', $irs);

            $irs->update($request->validated());

            return $this->sendResponse(
                new InternalRevenueServiceResource($irs->load('state')),
                'Internal Revenue Service updated successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error updating Internal Revenue Service: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified IRS
     */
    public function destroy($id): JsonResponse
    {
        try {
            $irs = InternalRevenueService::findOrFail($id);
            $this->authorize('delete', $irs);

            $irs->delete();

            return $this->sendResponse(
                null,
                'Internal Revenue Service deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error deleting Internal Revenue Service: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Bulk import IRS data
     */
    public function bulkImport(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', InternalRevenueService::class);

            $request->validate([
                'data' => 'required|array',
                'data.*.irs_name' => 'required|string',
                'data.*.short_name' => 'required|string',
                'data.*.state_name' => 'required|string',
                'data.*.website' => 'nullable|url',
                'data.*.contacts' => 'required|array',
            ]);

            $imported = 0;
            $errors = [];

            foreach ($request->data as $index => $irsData) {
                try {
                    // Find state by name
                    $state = \App\Models\State::where('name', $irsData['state_name'])->first();
                    
                    if (!$state) {
                        $errors[] = "Row {$index}: State '{$irsData['state_name']}' not found";
                        continue;
                    }

                    // Check if IRS already exists for this state - FIXED: Use correct model
                    $existing = InternalRevenueService::where('state_id', $state->id)->first();
                    if ($existing) {
                        // Update existing
                        $existing->update([
                            'irs_name' => $irsData['irs_name'],
                            'short_name' => $irsData['short_name'],
                            'website' => $irsData['website'],
                            'contacts' => $irsData['contacts'],
                        ]);
                    } else {
                        // Create new
                        InternalRevenueService::create([
                            'irs_name' => $irsData['irs_name'],
                            'short_name' => $irsData['short_name'],
                            'state_id' => $state->id,
                            'website' => $irsData['website'],
                            'contacts' => $irsData['contacts'],
                        ]);
                    }

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Row {$index}: " . $e->getMessage();
                }
            }

            return $this->sendResponse([
                'imported' => $imported,
                'errors' => $errors
            ], "Successfully imported {$imported} records");

        } catch (\Exception $e) {
            return $this->sendError(
                'Error during bulk import: ' . $e->getMessage(),
                500
            );
        }
    }
}