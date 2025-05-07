@extends('layouts.master')

@section('content')
    <div class="p-6">
        <!-- Dashboard Heading -->
        <h2 class="text-2xl font-semibold mb-4">Dashboard</h2>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 shadow rounded-lg">
                <h3 class="text-lg font-medium">Total Users</h3>
                <p class="text-2xl font-bold">{{-- {{ $totalUsers }} --}}</p>
            </div>
            <div class="bg-white p-4 shadow rounded-lg">
                <h3 class="text-lg font-medium">Total Transactions</h3>
                <p class="text-2xl font-bold">{{-- {{ $totalTransactions }} --}}</p>
            </div>
            <div class="bg-white p-4 shadow rounded-lg">
                <h3 class="text-lg font-medium">Revenue</h3>
                <p class="text-2xl font-bold">{{-- &#8358;{{ number_format($totalRevenue, 2) }} --}}</p>
            </div>
            <div class="bg-white p-4 shadow rounded-lg">
                <h3 class="text-lg font-medium">Pending Withdrawals</h3>
                <p class="text-2xl font-bold">{{-- &#8358;{{ number_format($pendingWithdrawals, 2) }} --}}</p>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white mt-6 p-4 shadow rounded-lg">
            <h3 class="text-xl font-medium mb-4">Recent Transactions</h3>
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">User</th>
                        <th class="p-2 border">Amount</th>
                        <th class="p-2 border">Status</th>
                        <th class="p-2 border">Date</th>
                    </tr>
                </thead>
                {{-- <tbody>
                    @foreach ($recentTransactions as $transaction)
                        <tr class="border">
                            <td class="p-2 border">{{ $transaction->user->name }}</td>
                            <td class="p-2 border">&#8358;{{ number_format($transaction->amount, 2) }}</td>
                            <td class="p-2 border">{{ ucfirst($transaction->status) }}</td>
                            <td class="p-2 border">{{ $transaction->created_at->format('d M, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody> --}}
            </table>
        </div>
    </div>
@endsection
