<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Validation\Rule;

#[FailOnUnknownFields]
class ReservationStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * https://readouble.com/laravel/13.x/ja/validation.html
     *
     * client_id	必須。利用者として存在し、論理削除されていない	required、exists（条件の追加は下の注意を参照）
     * date	必須。2026-10-14 の形	required、date_format
     * start_time	必須。09:00 の形	required、date_format（時刻の形の書き方を調べる）
     * end_time	必須。時刻の形。開始時刻より後	上に加えて after（比べる相手の項目名を指定できる）
     * staff_ids	空でもよい配列	array（required は付けない）
     * staff_ids.*	配列の中身1つずつが、存在して論理削除されていない職員	exists
     * support_type_id	必須。存在して論理削除されていない	required、exists
     * vehicle_type_choice	必須。4つの値のどれか	required、in
     * remarks	空でもよい文字列	nullable、string
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                Rule::exists('client', 'id')->withoutTrashed(),
            ],
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
            // 'title.required' => ':attribute は必須です。',
            // 'body.min' => ':attribute は最低 :min 文字必要です。',
        ];
    }


    public function attributes()
    {
        return [
            // 'title' => 'タイトル',
            // 'body' => '本文',
        ];
    }
}
