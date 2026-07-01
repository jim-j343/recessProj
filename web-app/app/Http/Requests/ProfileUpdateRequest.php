<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:80'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                // Your primary key is user_id, not id
                Rule::unique(User::class, 'email')->ignore($this->user()->user_id, 'user_id'),
            ],
        ];
    }
}
