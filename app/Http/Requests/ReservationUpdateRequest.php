<?php

namespace App\Http\Requests;
use App\Services\CurrentStaffService;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationUpdateRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'staff_ids' => ['array', 'nullable'],
            'staff_ids.*' => [
                Rule::exists('staff', 'id')->withoutTrashed(),
            ],
            'support_type_id' => [
                'required',
                Rule::exists('support_type', 'id')->withoutTrashed(),
            ],
            'vehicle_type_choice' => [
                'required',
                Rule::in(['care_vehicle', 'company_car', 'private_car', 'none'])
            ],
            'remarks' => ['nullable', 'string']
        ];
    }

    public function messages()
    {
        return [
            'end_time.after' => '終了時刻は、開始時刻より後の時刻を指定してください。',
            'client_id.required' => ':attributeを選択してください。',
            'support_type_id.required' => ':attributeを選択してください。',
            'vehicle_type_choice.required' => ':attributeを選択してください。',
        ];
    }


    public function attributes()
    {
        return [
            'client_id' => '利用者',
            'date' => '予約日',
            'start_time' => '予約開始時間',
            'end_time' => '予約終了時間',
            'staff_ids.*' => '担当者',
            'support_type_id' => '支援内容',
            'vehicle_type_choice' => '車両',
            'remarks' => '備考',
        ];
    }
}
