<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Fee;
use App\Models\Tax;
use App\Models\ServiceFees;

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
                'recent_transactions' => Fee::with('user')->latest()->take(5)->get(),
                'recent_users' => User::latest()->take(5)->get()
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
