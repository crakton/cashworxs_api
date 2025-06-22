<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $stateId = $this->route('state') ?? $this->route('id');
        
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('states', 'name')->ignore($stateId)
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::unique('states', 'code')->ignore($stateId)
            ],
            'capital' => 'nullable|string|max:100',
            'zone' => 'nullable|string|max:50',
            'region' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'State name is required',
            'name.unique' => 'State name already exists',
            'name.max' => 'State name cannot exceed 100 characters',
            'code.required' => 'State code is required',
            'code.unique' => 'State code already exists',
            'code.max' => 'State code cannot exceed 10 characters',
            'capital.max' => 'Capital name cannot exceed 100 characters',
            'zone.max' => 'Zone cannot exceed 50 characters',
            'region.max' => 'Region cannot exceed 50 characters',
            'is_active.boolean' => 'Active status must be true or false',
        ];
    }
}