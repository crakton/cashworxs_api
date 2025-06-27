<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends BaseController
{
    /**
     * Get all users except current user with role-based filtering
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();
        
        $query = User::with(['roles', 'stateInfo'])
            ->where('id', '!=', $currentUser->id);

        // Apply role-based filtering
        if ($currentUser->hasRole('operator')) {
            // Operators can only see users in their state
            $query->where('state_id', $currentUser->state_id);
        } elseif ($currentUser->hasRole('irs_specialist')) {
            // IRS specialists can only see users in their state
            $query->where('state_id', $currentUser->state_id);
        }
        // Admins can see all users (no additional filtering)

        $users = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'phone' => $user->phone_number,
                'email' => $user->email,
                'role' => $user->role,
                'organization' => [
                    'id' => '1', // You might want to add organization relationship
                    'name' => 'Default Organization' // Update based on your org structure
                ],
                'state' => $user->stateInfo ? $user->stateInfo->name : 'Unknown',
                'isActive' => $user->verified ?? true,
                'createdAt' => $user->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Create a new user
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();

        // Validate input
        $validator = $request->validate( [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^\+\d{10,15}$/|unique:users,phone_number',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,operator,irs_specialist',
            'organizationId' => 'required|string', // You might want to validate this against organizations table
            'state' => 'required|string|exists:states,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check permissions
        if (!$this->canCreateUser($currentUser, $request->role)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create users with this role'
            ], 403);
        }

        // Get state ID
        $state = State::where('name', $request->state)->first();
        if (!$state) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid state'
            ], 422);
        }

        // Create user
        $user = User::create([
            'full_name' => $request->name,
            'phone_number' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'state_id' => $state->id,
            'verified' => true,
            'push_notifications_enabled' => true,
        ]);

        // Assign role via relationship if needed
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->assignRole($role);
        }

        // Load relationships for response
        $user->load(['roles', 'stateInfo']);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'phone' => $user->phone_number,
                'email' => $user->email,
                'role' => $user->role,
                'organization' => [
                    'id' => $request->organizationId,
                    'name' => 'Default Organization' // Update based on your org structure
                ],
                'state' => $user->stateInfo->name,
                'isActive' => $user->verified,
                'createdAt' => $user->created_at->toISOString(),
            ]
        ], 201);
    }

    /**
     * Update user status (activate/deactivate)
     */
    public function updateStatus(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check permissions
        if (!$this->canModifyUser($currentUser, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to modify this user'
            ], 403);
        }

        // Validate input
        $validator = $request->validate( [
            'isActive' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update status
        $user->verified = $request->isActive;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'userId' => $user->id,
                'isActive' => $user->verified
            ]
        ]);
    }

    /**
     * Update user details
     */
    public function update(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check permissions
        if (!$this->canModifyUser($currentUser, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to modify this user'
            ], 403);
        }

        // Validate input
        $validator = $request->validate( [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|regex:/^\+\d{10,15}$/|unique:users,phone_number,' . $user->id,
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|required|in:admin,operator,irs_specialist',
            'state' => 'sometimes|required|string|exists:states,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check role change permissions
        if ($request->has('role') && !$this->canCreateUser($currentUser, $request->role)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to assign this role'
            ], 403);
        }

        // Update user
        $updateData = [];
        
        if ($request->has('name')) {
            $updateData['full_name'] = $request->name;
        }
        
        if ($request->has('phone')) {
            $updateData['phone_number'] = $request->phone;
        }
        
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        
        if ($request->has('role')) {
            $updateData['role'] = $request->role;
            
            // Update role relationship
            $role = Role::where('name', $request->role)->first();
            if ($role) {
                $user->syncRoles([$role]);
            }
        }
        
        if ($request->has('state')) {
            $state = State::where('name', $request->state)->first();
            if ($state) {
                $updateData['state_id'] = $state->id;
            }
        }

        $user->update($updateData);
        $user->load(['roles', 'stateInfo']);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'phone' => $user->phone_number,
                'email' => $user->email,
                'role' => $user->role,
                'organization' => [
                    'id' => '1',
                    'name' => 'Default Organization'
                ],
                'state' => $user->stateInfo->name,
                'isActive' => $user->verified,
                'createdAt' => $user->created_at->toISOString(),
            ]
        ]);
    }

    /**
     * Delete user (soft delete or hard delete based on permissions)
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check permissions - only admins and irs_specialists can delete
        if (!$this->canDeleteUser($currentUser, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this user'
            ], 403);
        }

        // Delete user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get available states
     */
    public function getStates()
    {
        $states = State::select('id', 'name')->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $states->pluck('name')->toArray()
        ]);
    }

    /**
     * Get available roles based on current user permissions
     */
    public function getRoles(Request $request)
    {
        try {
            $currentUser = $request->user();
        $roles = [];

        if ($currentUser->hasRole('admin')) {
            $roles = ['operator', 'irs_specialist']; // Admin can create operator and irs_specialist
        } elseif ($currentUser->hasRole('operator')) {
            $roles = []; // Operators cannot create users
        } elseif ($currentUser->hasRole('irs_specialist')) {
            $roles = ['operator']; // IRS specialists can create operators in their state
        }

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
        return $this->sendResponse($roles, 'Available roles retrieved successfully');
        } catch (\Exception $e) {
           return $this->sendError($e, 'Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if current user can create a user with specified role
     */
    private function canCreateUser($currentUser, $role)
    {
        if ($currentUser->hasRole('admin')) {
            // Admins can create operator and irs_specialist, but not other admins
            return in_array($role, ['operator', 'irs_specialist']);
        }
        
        if ($currentUser->hasRole('irs_specialist')) {
            // IRS specialists can create operators
            return $role === 'operator';
        }
        
        // Operators cannot create users
        return false;
    }

    /**
     * Check if current user can modify specified user
     */
    private function canModifyUser($currentUser, $targetUser)
    {
        // Cannot modify yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Admins can modify anyone except other admins
        if ($currentUser->hasRole('admin')) {
            return !$targetUser->hasRole('admin');
        }

        // Operators can update users in their state (but not delete)
        if ($currentUser->hasRole('operator')) {
            return $currentUser->state_id === $targetUser->state_id;
        }

        // IRS specialists can modify users in their state
        if ($currentUser->hasRole('irs_specialist')) {
            return $currentUser->state_id === $targetUser->state_id;
        }

        return false;
    }

    /**
     * Check if current user can delete specified user
     */
    private function canDeleteUser($currentUser, $targetUser)
    {
        // Cannot delete yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Admins can delete anyone except other admins
        if ($currentUser->hasRole('admin')) {
            return !$targetUser->hasRole('admin');
        }

        // IRS specialists can delete users in their state
        if ($currentUser->hasRole('irs_specialist')) {
            return $currentUser->state_id === $targetUser->state_id;
        }

        // Operators cannot delete users
        return false;
    }
}
