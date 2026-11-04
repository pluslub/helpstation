<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'login_id' => [ 'required', 'string', 'alpha_dash:ascii' ],
            'password' => [ 'required', 'string' ],
        ];
    }

    public function messages()
    {
        return [
            'login_id.alpha_dash' => ':attributeは半角英数字で入力してください。',
        ];
    }

    public function attributes()
    {
        return [
            'login_id' => 'ID',
            'password' => 'パスワード',
        ];
    }

    protected function prepareForValidation(){
        $inputLoginId = $this->input('login_id', '');
        $convertedLoginId = trim(mb_convert_kana($inputLoginId, 'as'));
        $this->merge(['login_id' => $convertedLoginId]);
    }
}
