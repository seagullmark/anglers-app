<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFishingTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_date' => ['required', 'date'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'river_name' => ['required', 'string', 'max:100'],
            'point_name' => ['required', 'string', 'max:100'],
            'tackle_name' => ['required', 'string', 'max:200'],
            'memo' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'mimes:png,jpeg,jpg', 'max:10240'],
        ];
    }
}
