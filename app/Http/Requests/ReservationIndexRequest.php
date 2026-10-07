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
        return true;    // 権限チェックは、ログイン画面を作るときに見直す
                        //falseだと403エラー
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /*
             * URL(/?week=2026-10-06)のバリデーション
             * nullable：/?week=は/になる。
             * 受け取るフォーマットは'Y-m-d'
             */
            'week' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
