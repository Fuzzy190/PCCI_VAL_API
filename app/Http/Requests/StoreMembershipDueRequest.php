<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipDueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins and treasurers can create dues
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
            'member_id' => ['required', 'exists:members,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'due_date' => ['required', 'date'],
            'status' => ['nullable', 'in:pending,paid,overdue,waived,expired'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => 'Member is required',
            'member_id.exists' => 'Member does not exist',
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be greater than 0',
            'due_year.required' => 'Due year is required',
            'due_year.integer' => 'Due year must be a valid year',
            'due_date.required' => 'Due date is required',
            'due_date.date' => 'Due date must be a valid date',
            'status.in' => 'Invalid status. Must be one of: pending, paid, overdue, waived, expired',
        ];
    }
}
