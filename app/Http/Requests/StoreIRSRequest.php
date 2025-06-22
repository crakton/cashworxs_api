<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIRSRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'irs_name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'state_id' => 'required|exists:states,id|unique:internal_revenue_services,state_id',
            'website' => 'nullable|url|max:255',
            'contacts' => 'required|array|min:1',
            'contacts.*.name' => 'required|string|max:100',
            'contacts.*.position' => 'required|string|max:100',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.email' => 'nullable|email|max:100',
            'contacts.*.office_address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'irs_name.required' => 'IRS name is required',
            'irs_name.max' => 'IRS name cannot exceed 255 characters',
            'short_name.required' => 'Short name is required',
            'short_name.max' => 'Short name cannot exceed 50 characters',
            'state_id.required' => 'State is required',
            'state_id.exists' => 'Selected state does not exist',
            'state_id.unique' => 'An IRS record already exists for this state',
            'website.url' => 'Website must be a valid URL',
            'website.max' => 'Website URL cannot exceed 255 characters',
            'contacts.required' => 'At least one contact is required',
            'contacts.array' => 'Contacts must be an array',
            'contacts.min' => 'At least one contact is required',
            'contacts.*.name.required' => 'Contact name is required',
            'contacts.*.name.max' => 'Contact name cannot exceed 100 characters',
            'contacts.*.position.required' => 'Contact position is required',
            'contacts.*.position.max' => 'Contact position cannot exceed 100 characters',
            'contacts.*.phone.max' => 'Phone number cannot exceed 20 characters',
            'contacts.*.email.email' => 'Contact email must be valid',
            'contacts.*.email.max' => 'Contact email cannot exceed 100 characters',
            'contacts.*.office_address.max' => 'Office address cannot exceed 500 characters',
            'is_active.boolean' => 'Active status must be true or false',
        ];
    }
}