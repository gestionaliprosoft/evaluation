<?php

namespace App\Filament\Resources\UserResource\Api\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'required',
            'team_id' => 'required',
            'stripe_customer_id' => 'required',
            'name' => 'required',
            'surname' => 'required',
            'email' => 'required',
            'email_verified_at' => 'required',
            'password' => 'required',
            'remember_token' => 'required',
            'currency' => 'required',
            'number_decimals' => 'required',
            'timezone' => 'required',
            'avatar_url' => 'required',
            'lang' => 'required',
            'enabled' => 'required',
            'active_status' => 'required',
            'avatar' => 'required',
            'dark_mode' => 'required',
            'messenger_color' => 'required',
        ];
    }
}
