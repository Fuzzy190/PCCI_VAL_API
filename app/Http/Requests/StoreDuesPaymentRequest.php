<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDuesPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only treasurers and admins can record payments
        return $this->user()->hasAnyRole(['super_admin', 'admin', 'treasurer']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'membership_due_id' => ['required', 'exists:membership_dues,id'],
            'or_number' => ['required', 'string', 'unique:dues_payments,or_number'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,check,bank_transfer,online,mobile_money'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'membership_due_id.required' => 'Membership due is required',
            'membership_due_id.exists' => 'Membership due does not exist',
            'or_number.required' => 'Official Receipt number is required',
            'or_number.unique' => 'This OR number already exists',
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be greater than 0',
            'payment_date.required' => 'Payment date is required',
            'payment_date.date' => 'Payment date must be a valid date',
            'payment_method.in' => 'Invalid payment method. Must be one of: cash, check, bank_transfer, online, mobile_money',
        ];
    }
}
