<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Models\IdConfig;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class IdConfigController extends BaseController
{
    /**
     * Check if user has permission to access organization
     */
    private function checkOrganizationAccess(Request $request, $orgId, $action = 'view')
    {
        $user = $request->user();
        
        if (!$user) {
            throw new Exception('Unauthorized', 401);
        }

        // Admin can access all organizations
        if ($user->hasRole('admin')) {
            return $user;
        }

        // Operators and IRS specialists can view but not modify
        if (in_array($action, ['view', 'index']) && $user->hasAnyRole(['operator', 'irs_specialist'])) {
            return $user;
        }

        // Only admins can create, update, delete
        if (in_array($action, ['create', 'update', 'delete']) && !$user->hasRole('admin')) {
            throw new Exception('Permission denied', 403);
        }

        throw new Exception('Permission denied', 403);
    }

    /**
     * Get all ID configurations for an organization
     */
    public function index(Request $request, $orgId)
    {
        try {
            // Check authorization
            $user = $this->checkOrganizationAccess($request, $orgId, 'index');

            $organization = Organization::findOrFail($orgId);

            // Get ID configurations with proper relationship
            $idConfigs = IdConfig::where('organization_id', $organization->id)
                ->where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            return $this->sendResponse([
                'data' => $idConfigs,
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name ?? 'Unknown Organization'
                ]
            ], 'ID configurations retrieved successfully');

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Organization not found', [], 404);
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->sendError($e->getMessage(), [], $statusCode);
        }
    }

    /**
     * Create a new ID configuration
     */
    public function store(Request $request, $orgId)
    {
        try {
            // Check authorization
            $user = $this->checkOrganizationAccess($request, $orgId, 'create');

            $organization = Organization::findOrFail($orgId);

            // Validate the request data
            $validated = $request->validate([
                'field_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('id_configs')->where(function ($query) use ($organization) {
                        return $query->where('organization_id', $organization->id)
                                   ->where('is_active', true);
                    })
                ],
                'field_label' => 'required|string|max:255',
                'field_type' => 'required|in:text,number,email,phone,file',
                'is_required' => 'boolean',
                'validation_rules' => 'nullable|json',
                'help_text' => 'nullable|string',
                'sort_order' => 'integer|min:0',
                'is_active' => 'boolean',
            ]);

            // Set default values
            $validated['organization_id'] = $organization->id;
            $validated['is_required'] = $validated['is_required'] ?? false;
            $validated['is_active'] = $validated['is_active'] ?? true;
            $validated['sort_order'] = $validated['sort_order'] ?? 0;

            $config = DB::transaction(function () use ($validated) {
                return IdConfig::create($validated);
            });

            return $this->sendResponse([
                'data' => $config->fresh()
            ], 'ID configuration created successfully', 201);

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Organization not found', [], 404);
        } catch (ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->sendError($e->getMessage(), [], $statusCode);
        }
    }

    /**
     * Get a specific ID configuration
     */
    public function show(Request $request, $orgId, $idConfigId)
    {
        try {
            // Check authorization
            $user = $this->checkOrganizationAccess($request, $orgId, 'view');

            $organization = Organization::findOrFail($orgId);
            $idConfig = IdConfig::where('organization_id', $organization->id)
                ->findOrFail($idConfigId);

            return $this->sendResponse([
                'data' => $idConfig,
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name ?? 'Unknown Organization'
                ]
            ], 'ID configuration retrieved successfully');

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Organization or ID configuration not found', [], 404);
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->sendError($e->getMessage(), [], $statusCode);
        }
    }

    /**
     * Update an ID configuration
     */
    public function update(Request $request, $orgId, $idConfigId)
    {
        try {
            // Check authorization
            $user = $this->checkOrganizationAccess($request, $orgId, 'update');

            $organization = Organization::findOrFail($orgId);
            $idConfig = IdConfig::where('organization_id', $organization->id)
                ->findOrFail($idConfigId);

            $validated = $request->validate([
                'field_name' => [
                    'sometimes',
                    'string',
                    'max:255',
                    Rule::unique('id_configs')->where(function ($query) use ($organization) {
                        return $query->where('organization_id', $organization->id)
                                   ->where('is_active', true);
                    })->ignore($idConfig->id)
                ],
                'field_label' => 'sometimes|string|max:255',
                'field_type' => 'sometimes|in:text,number,email,phone,file',
                'is_required' => 'sometimes|boolean',
                'validation_rules' => 'nullable|json',
                'help_text' => 'nullable|string',
                'sort_order' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            $idConfig->update($validated);

            return $this->sendResponse([
                'data' => $idConfig->fresh()
            ], 'ID configuration updated successfully');

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Organization or ID configuration not found', [], 404);
        } catch (ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->sendError($e->getMessage(), [], $statusCode);
        }
    }

    /**
     * Delete an ID configuration (soft delete by marking as inactive)
     */
    public function destroy(Request $request, $orgId, $idConfigId)
    {
        try {
            // Check authorization
            $user = $this->checkOrganizationAccess($request, $orgId, 'delete');

            $organization = Organization::findOrFail($orgId);
            $idConfig = IdConfig::where('organization_id', $organization->id)
                ->findOrFail($idConfigId);

            // Soft delete by marking as inactive instead of hard delete
            $idConfig->update(['is_active' => false]);

            return $this->sendResponse([], 'ID configuration deleted successfully');

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Organization or ID configuration not found', [], 404);
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->sendError($e->getMessage(), [], $statusCode);
        }
    }

    /**
     * Get available field types
     */
    public function getFieldTypes(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || !$user->hasAnyRole(['admin', 'operator', 'irs_specialist'])) {
                throw new Exception('Permission denied', 403);
            }

            $fieldTypes = [
                'text' => 'Text',
                'number' => 'Number',
                'email' => 'Email',
                'phone' => 'Phone',
                'file' => 'File'
            ];

            return $this->sendResponse([
                'data' => $fieldTypes
            ], 'Field types retrieved successfully');

        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->sendError($e->getMessage(), [], $statusCode);
        }
    }

    /**
     * Reorder ID configurations
     */
    public function reorder(Request $request, $orgId)
    {
        try {
            // Check authorization
            $user = $this->checkOrganizationAccess($request, $orgId, 'update');

            $organization = Organization::findOrFail($orgId);

            $validated = $request->validate([
                'configs' => 'required|array',
                'configs.*.id' => 'required|exists:id_configs,id',
                'configs.*.sort_order' => 'required|integer|min:0'
            ]);

            DB::transaction(function () use ($validated, $organization) {
                foreach ($validated['configs'] as $configData) {
                    IdConfig::where('id', $configData['id'])
                        ->where('organization_id', $organization->id)
                        ->update(['sort_order' => $configData['sort_order']]);
                }
            });

            return $this->sendResponse([], 'ID configurations reordered successfully');

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Organization not found', [], 404);
        } catch (ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->sendError($e->getMessage(), [], $statusCode);
        }
    }
}