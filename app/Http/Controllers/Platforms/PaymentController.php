<?php

namespace App\Http\Controllers\Platforms;

use App\Http\Controllers\Api\BaseController;
use App\Models\Fee;
use App\Models\Tax;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{



    public function paymentOptions()
    {
        try {

            // mock payment options
            $options = [
                ['id' => 1, 'method' => 'Bank Transfer'],
                ['id' => 2, 'method' => 'Credit Card'],
            ];
            return $this->sendResponse($options, 'Supported payment options');
            //code...
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function processTaxPayment(Request $request)
    {
        try {
            $request->validate([
                'tax_id' => 'required|string',
                'amount' => 'required|numeric',
                'payment_method' => 'required|string|in:bank_transfer,credit_card',
                'metadata' => 'nullable|array'
            ]);

            $user = $request->user();

            // mock successful tax payment processing
            $payment = [
                'tax_id' => $request->tax_id,
                'user_id' => $user->id,
                'transaction_type' => $request->payment_method,
                'transaction_amount' => $request->amount,
                'transaction_status' => 'success',
                'transaction_ref' => 'TRX-' . rand(1000, 9999),

            ];
            // record transaction to the transaction table and update tax checkout

            Transaction::create($payment);
            $tax = Tax::all()->where('user_id', $user->id)->where('id', $request->tax_id)->first();
            $tax->update(['tax_status' => 'paid']);

            return $this->sendResponse(['result' => $payment], 'Tax payment successful');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $tax = Tax::all()->where('user_id', $user->id)->where('id', $request->tax_id)->first();
            $tax->update(['tax_status' => 'failed']);
            return $this->sendError(['message' => 'Tax payment failed', 'Failed transaction', 403]);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function processFeePayment(Request $request)
    {
        try {
            $request->validate([
                'fee_id' => 'required|string',
                'amount' => 'required|numeric',
                'payment_method' => 'required|string|in:bank_transfer,credit_card',
                'metadata' => 'nullable|array'
            ]);

            $user = $request->user();

            // mock successful fee payment processing
            $payment = [
                'fee_id' => $request->fee_id,
                'user_id' => $user->id,
                'transaction_type' => $request->payment_method,
                'transaction_amount' => $request->amount,
                'transaction_status' => 'success',
                'transaction_ref' =>  'TRX-' . rand(1000, 9999),

            ];
            // record transaction to the transaction table and update fee checkout

            Transaction::create($payment);
            $fee = Fee::all()->where('user_id', $user->id)->where('id', $request->fee_id)->first();
            $fee->update(['fee_status' => 'paid']);

            return $this->sendResponse(['result' => $payment], 'Fee payment successful');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $fee = Fee::all()->where('user_id', $user->id)->where('id', $request->fee_id)->first();
            $fee->update(['fee_status' => 'failed']);
            return $this->sendError(['message' => 'Tax payment failed', 'Failed transaction', 403]);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
