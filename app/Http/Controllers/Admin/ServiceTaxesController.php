<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\ServiceFees;
use Illuminate\Http\Request;

class ServiceTaxesController extends BaseController
{
  public function getServiceTaxes()
  {
    try {
      $fees = ServiceFees::all();
      return $this->sendResponse(['fees' => $fees]);
    } catch (\Exception $e) {
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  public function createServiceTax(Request $request)
  {
    try {
      $request->validate([
        'name' => 'required|string',
        'type' => 'required|string',
        'state' => 'required|string',
        'amount' => 'required|numeric',
        'description' => 'nullable|string',
        'status' => 'boolean',
        'metadata' => 'nullable|array',
      ]);

      $fee = ServiceFees::create($request->all());
      return $this->sendResponse(['fee' => $fee], 'Service fee created', 201);
    } catch (\Exception $e) {
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  public function updateServiceTax(Request $request, $id)
  {
    try {
      $request->validate([
        'name' => 'nullable|string',
        'type' => 'nullable|string',
        'state' => 'nullable|string',
        'amount' => 'nullable|numeric',
        'description' => 'nullable|string',
        'status' => 'boolean',
        'metadata' => 'nullable|array',
      ]);

      $fee = ServiceFees::findOrFail($id);
      $fee->update($request->all());
      return $this->sendResponse(['fee' => $fee], 'Service fee updated');
    } catch (\Exception $e) {
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }

  public function deleteServiceTax($id)
  {
    try {
      ServiceFees::findOrFail($id)->delete();
      return $this->sendResponse([], 'Service fee deleted');
    } catch (\Exception $e) {
      return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
    }
  }
}
