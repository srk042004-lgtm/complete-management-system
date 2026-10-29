<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterHotelRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $website = trim((string) $this->input('website', ''));

        if ($website !== '' && ! preg_match('/^https?:\/\//i', $website)) {
            $this->merge(['website' => 'https://' . $website]);
        }
    }

    public function authorize(): bool
    {
        return ! $this->user();
    }

    public function rules(): array
    {
        return [
            'hotel_name' => ['required', 'string', 'max:255'],
            'hotel_type' => ['required', 'string', 'max:100'],
            'other_hotel_type' => ['nullable', 'required_if:hotel_type,Other', 'string', 'max:100'],
            'total_rooms' => ['required', 'integer', 'min:1', 'max:100000'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'hotel_phone' => ['required', 'string', 'max:50'],
            'hotel_email' => ['required', 'email', 'max:255', 'unique:tenants,email'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_phone' => ['required', 'string', 'max:50'],
            'username' => ['required', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'business_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['nullable', 'string', 'max:50'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'reception_phone' => ['nullable', 'string', 'max:50'],
            'reservation_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'contact_address' => ['nullable', 'string', 'max:1000'],
            'terms_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hotel_phone' => 'hotel phone number',
            'hotel_email' => 'hotel email',
            'admin_name' => 'owner/manager full name',
            'admin_email' => 'admin email address',
            'admin_phone' => 'admin mobile number',
            'currency_code' => 'currency',
            'terms_accepted' => 'Terms & Conditions agreement',
            'privacy_accepted' => 'Privacy Policy agreement',
        ];
    }
}
