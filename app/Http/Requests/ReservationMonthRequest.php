<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReservationMonthRequest extends FormRequest
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
        //configのデータ'yyyy-MM-dd'を'yyyy-MM'に変換する
        $minDate = substr(config('helpstation_setting.selectable_min_date'), 0, 7);

        return [
            /*
             * URL(/reservations/monthly/?month=2026-10)のバリデーション
             * 受け取るフォーマットは'Y-m'
             */
            'month' => ['nullable', 'date_format:Y-m', 'after_or_equal:'.$minDate],
        ];
    }
}
