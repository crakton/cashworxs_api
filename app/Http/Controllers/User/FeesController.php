<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Api\BaseController;
use App\Models\Fee;
use Illuminate\Http\Request;

class FeesController extends BaseController
{
    public function getUserFees(Request $request)
    {
        try {
            $fees = Fee::all()->where('user_id', $request->user()->id);
            return $this->sendResponse([
                'fees' => $fees
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function checkoutFee(Request $request)
    {
        try {
            $request->validate([
                'fee_type' => 'required|string',
                'fee_name' => 'required|string',
                'fee_amount' => 'required|numeric',
                'fee_metadata' => 'nullable|array',
            ]);

            $user = $request->user();
            $fee = Fee::create([
                'user_id' => $user->id,
                'fee_type' => $request->fee_type,
                'fee_name' => $request->fee_name,
                'fee_amount' => $request->fee_amount,
                'fee_metadata' => $request->fee_metadata,
                'fee_status' => 'pending',
            ]);

            return $this->sendResponse([
                'fee' => $fee
            ], 'Fees created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
    public function getUserFee(Request $request, $id)
    {
        try {

            $user = $request->user();
            $fee = Fee::all()->where('user_id', $user->id)->where('id', $id)->first();
            return $this->sendResponse([
                'fee' => $fee
            ], 'Fee details', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function updateUserFee(Request $request, $id)
    {
        try {
            $request->validate([
                'fee_type' => 'nullable|string',
                'fee_name' => 'nullable|string',
                'fee_amount' => 'nullable|numeric',
                'fee_metadata' => 'nullable|array',
            ]);

            $user = $request->user();
            $fee = Fee::all()->where('user_id', $user->id)->where('id', $id)->first();
            $fee->fee_type = $request->fee_type;
            $fee->fee_name = $request->fee_name;
            $fee->fee_amount = $request->fee_amount;
            $fee->fee_metadata = $request->fee_metadata;
            $fee->save();
            return $this->sendResponse([
                'fee' => $fee
            ], 'Fee details updated successfully', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function dropUserFee(Request $request, $id)
    {
        try {
            $user = $request->user();
            $fee = Fee::all()->where('user_id', $user->id)->where('id', $id)->first();
            $fee->delete();
            return $this->sendResponse('Fee deleted successfully', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
