<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\UpdateStateRequest;
use App\Http\Resources\StateResource;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StateController extends Controller
{
    /**
     * Display a listing of states
     */
    public function index(Request $request): JsonResponse
    {
        $query = State::with(['internalRevenueService']);

        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        $states = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => StateResource::collection($states)
        ]);
    }

    /**
     * Store a newly created state
     */
    public function store(StoreStateRequest $request): JsonResponse
    {
        $this->authorize('create', State::class);

        $state = State::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'State created successfully',
            'data' => new StateResource($state)
        ], 201);
    }

    /**
     * Display the specified state
     */
    public function show($id): JsonResponse
    {
        $state = State::with(['internalRevenueService', 'users'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new StateResource($state)
        ]);
    }

    /**
     * Update the specified state
     */
    public function update(UpdateStateRequest $request, $id): JsonResponse
    {
        $state = State::findOrFail($id);
        $this->authorize('update', $state);

        $state->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'State updated successfully',
            'data' => new StateResource($state)
        ]);
    }

    /**
     * Remove the specified state
     */
    public function destroy($id): JsonResponse
    {
        $state = State::findOrFail($id);
        $this->authorize('delete', $state);

        // Check if state has associated users
        if ($state->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete state with associated users'
            ], 400);
        }

        $state->delete();

        return response()->json([
            'success' => true,
            'message' => 'State deleted successfully'
        ]);
    }
}
