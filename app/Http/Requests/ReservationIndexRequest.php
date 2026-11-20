<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReservationIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;    //ログインしている職員は全員見られる。ログインの確認は auth ミドルウェアで行う
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $minDate = config('helpstation_setting.selectable_min_date');
        $maxDate = config('helpstation_setting.selectable_max_date');

        return [
            /*
             * URL(/?week=2026-10-06)のバリデーション
             * nullable：/?week=は/になる。
             * 受け取るフォーマットは'Y-m-d'
             */
            'week' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$minDate, 'before_or_equal:'.$maxDate],
        ];
    }
}
