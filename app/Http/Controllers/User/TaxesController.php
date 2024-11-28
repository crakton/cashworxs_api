<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxesController extends BaseController
{
    public function getUserTaxes(Request $request)
    {
        try {
            $user = $request->user();
            $taxes = Tax::all()->where('user_id', $user->id);
            return $this->sendResponse([
                'taxes' => $taxes
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function checkoutTax(Request $request)
    {
        try {
            $request->validate([
                'tax_type' => 'required|string',
                'tax_name' => 'required|string',
                'tax_year' => 'required|integer',
                'tax_amount' => 'required|integer',
                'gross_income' => 'required|numeric',
                'tax_metadata' => 'nullable|array',
            ]);
            $user = $request->user();
            $tax = Tax::create([
                'user_id' => $user->id,
                'tax_type' => $request->tax_type,
                'tax_name' => $request->tax_name,
                'tax_year' => $request->tax_year,
                'tax_amount' => $request->tax_amount,
                'tax_status' => 'pending',
                'gross_income' => $request->gross_income,
                'tax_metadata' => $request->tax_metadata,
            ]);
            return $this->sendResponse([
                'tax' => $tax
            ], 'Tax created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
    public function getUserTax(Request $request, $id)
    {
        try {

            $request->validate([
                'id' => 'required|integer',

            ]);
            $user = $request->user();
            $tax = Tax::all()->where('user_id', $user->id)->where('id', $id)->first();
            return $this->sendResponse([
                'tax' => $tax
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function updateUserTax(Request $request, $id)
    {
        try {
            $request->validate([
                'tax_type' => 'nullable|string',
                'tax_name' => 'nullable|string',
                'tax_year' => 'nullable|integer',
                'gross_income' => 'nullable|numeric',
                'tax_metadata' => 'nullable|array',
            ]);

            $user = $request->user();
            $tax = Tax::all()->where('user_id', $user->id)->where('id', $id)->first();
            $tax->tax_type = $request->tax_type;
            $tax->tax_name = $request->tax_name;
            $tax->tax_year = $request->tax_year;
            $tax->gross_income = $request->gross_income;
            $tax->tax_metadata = $request->tax_metadata;
            $tax->save();
            return $this->sendResponse([
                'tax' => $tax
            ], 'Tax details updated successfully', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function dropUserTax(Request $request, $id)
    {
        try {
            $user = $request->user();
            $tax = Tax::all()->where('user_id', $user->id)->where('id', $id)->first();
            $tax->delete();
            return $this->sendResponse('Tax deleted successfully', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
