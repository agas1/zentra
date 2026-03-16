<?php

namespace App\Domain\Workspace\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'role' => 'sometimes|string|in:admin,member',
        ];
    }
}
