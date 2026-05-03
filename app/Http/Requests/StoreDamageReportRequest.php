<?php

namespace App\Http\Requests;

use App\Models\DamageReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $damageKeys = array_keys(DamageReport::getDamageTypes());

        return [
            'damage_type' => ['required', 'string', 'max:50', Rule::in($damageKeys)],
            'description' => 'required|string|min:5',
            'severity' => 'required|in:low,medium,high,urgent',
            'status' => [
                'nullable',
                Rule::requiredIf(static fn () => request()->routeIs('staff.damage-reports.update')),
                Rule::in(['reported', 'in_progress', 'resolved', 'cancelled']),
            ],
            'room_id' => 'required|exists:rooms,id',
        ];
    }
}
