<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Fee;
use App\Models\Tax;
use App\Models\ServiceFees;
use App\Models\ServiceTaxes;
use App\Models\Organization;
use App\Models\Transaction;

class AdminDashboardController extends BaseController
{
    public function getDashboardStats()
    {
        try {
            // Base stats
            $stats = [
                'total_users' => User::count(),
                'total_service_fees' => ServiceFees::count(),
                'total_service_taxes' => ServiceTaxes::count(),
                'recent_transactions' => Transaction::with('user:id,fullname,type')
                    ->latest()
                    ->take(5)
                    ->get(),
                'recent_users' => User::latest()->take(5)->get(),
            ];

            // Weekly Overview Data
            $stats['weekly_data'] = $this->getWeeklyOverviewData();

            // Fee Distribution
            $stats['service_fees_business'] = ServiceFees::where('type', 'business')->count();
            $stats['service_fees_government'] = ServiceFees::where('type', 'government')->count();
            $stats['service_taxes_private'] = ServiceTaxes::where('type', 'private')->count();
            $stats['service_taxes_governmental'] = ServiceTaxes::where('type', 'governmental')->count();

            // Card Stats
            $stats['user_growth'] = $this->getUserGrowth();
            $stats['total_revenue'] = Transaction::where('transaction_type', 'payment')->sum('transaction_amount');
            $stats['new_transactions'] = Transaction::whereDate('created_at', today())->count();
            $stats['success_rate'] = $this->calculateSuccessRate();

            return $this->sendResponse($stats);
        } catch (\Exception $e) {
            return $this->sendError('Error fetching stats', $e->getMessage());
        }
    }

    private function getWeeklyOverviewData()
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        // Users per day
        $users = User::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        // Transactions per day
        $transactions = Transaction::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        // Format for chart
        $labels = [];
        $userData = [];
        $transactionData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('D');
            $userData[] = $users[$date] ?? 0;
            $transactionData[] = $transactions[$date] ?? 0;
        }

        return [
            'labels' => $labels,
            'users' => $userData,
            'transactions' => $transactionData
        ];
    }

    private function getUserGrowth()
    {
        $currentMonth = User::whereMonth('created_at', now()->month)->count();
        $lastMonth = User::whereMonth('created_at', now()->subMonth()->month)->count();

        return [
            'count' => $currentMonth,
            'percentage' => $lastMonth ? round(($currentMonth - $lastMonth) / $lastMonth * 100, 2) : 100
        ];
    }

    private function calculateSuccessRate()
    {
        $totalPayments = Transaction::where('transaction_type', 'payment')->count();
        $successfulPayments = Transaction::where('transaction_type', 'payment')
            ->where('transaction_status', 'completed')
            ->count();

        return $totalPayments ? round(($successfulPayments / $totalPayments) * 100, 2) : 0;
    }

    public function getOrganizations()
    {
        try {
            $organizations = Organization::all();
            return $this->sendResponse(['organizations' => $organizations], 'List of organizations');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    // create organization
    public function createOrganization(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'status' => 'boolean',
                'metadata' => 'nullable|array',
            ]);

            $organization = Organization::create($validated);

            return $this->sendResponse(['organization' => $organization], 'Organization created successfully', 201);
        } catch (\Exception $e) {
            \Log::error('Organization creation error: ' . $e->getMessage());
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
    // update organization
    public function updateOrganization(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string',
                'description' => 'nullable|string',
                'status' => 'boolean',
                'metadata' => 'nullable|array',
            ]);

            $organization = Organization::findOrFail($id);
            $organization->update($validated);

            return $this->sendResponse(['organization' => $organization], 'Organization updated successfully');
        } catch (\Exception $e) {
            \Log::error('Organization update error: ' . $e->getMessage());
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
    // delete organization
    public function deleteOrganization($id)
    {
        try {
            $organization = Organization::findOrFail($id);
            $organization->delete();

            return $this->sendResponse([], 'Organization deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Organization deletion error: ' . $e->getMessage());
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
    // get organization by id
    public function getOrganizationById($id)
    {
        try {
            $organization = Organization::findOrFail($id);
            return $this->sendResponse(['organization' => $organization], 'Organization details');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
