<?php

// app/Http/Requests/GenerateInvoiceRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    public function messages()
    {
        return [
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'due_date.after_or_equal' => 'The due date must be after or equal to the end date.',
        ];
    }
}