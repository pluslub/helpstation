<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\CurrentStaffService;

class ShiftDetailUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $loginStaff = app(CurrentStaffService::class)->getLoginStaff();
        return $loginStaff?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_time' => ['nullable', Rule::requiredIf(fn() => !($this->boolean('am_off'))), 'date_format:H:i',  ],
            'end_time' => ['nullable',
                            Rule::requiredIf(
                                fn() => !($this->boolean('pm_off'))),
                                'date_format:H:i',
                            Rule::when(
                                fn() =>
                                    !(($this->boolean('am_off')) && $this->boolean('pm_off'))
                                    &&  $this->filled('start_time') &&  $this->filled('end_time'),
                                'after:start_time')],
            'am_off' => ['required', 'boolean'],
            'pm_off' => ['required', 'boolean']
        ];
    }

    public function messages()
    {
        return [
            'end_time.after' => '終業時刻は、始業時刻より後の時刻を指定してください。',
        ];
    }


    public function attributes()
    {
        return [
            'start_time' => '始業時間',
            'end_time' => '終業時間',
            'am_off' => '午前休の選択',
            'pm_off' => '午後休の選択',
        ];
    }
}
