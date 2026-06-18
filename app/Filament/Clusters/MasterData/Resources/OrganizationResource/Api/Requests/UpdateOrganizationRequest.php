<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
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
            'team_id' => 'required',
            'user_id' => 'required',
            'name' => 'required',
            'primary_phone' => 'required',
            'secondary_phone' => 'required',
            'mobile_phone' => 'required',
            'legal_representative' => 'required',
            'primary_email' => 'required',
            'secondary_email' => 'required',
            'website' => 'required',
            'industry' => 'required',
            'rating' => 'required',
            'type' => 'required',
            'description' => 'required|string',
            'deleted_at' => 'required',
        ];
    }
}
