<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'due_date' => 'nullable|date|after_or_equal:date_to',
            'notes' => 'nullable|string|max:500',

            // New frequency validation
            'frequency' => 'nullable|in:weekly,fortnightly,monthly',

            // Conditional requirement for site_id
            'site_id' => Rule::requiredIf(function () {
                return in_array($this->input('type'), ['security_staff', 'client']);
            }),
        ];
    }

    public function messages()
    {
        return [
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'due_date.after_or_equal' => 'The due date must be after or equal to the end date.',
            'site_id.required' => 'The site field is required when type is security staff or client.',
            'frequency.required' => 'Please select a frequency for the invoice.',
            'frequency.in' => 'Invalid frequency selected. Choose Weekly, Fortnightly, or Monthly.',
        ];
    }
}
