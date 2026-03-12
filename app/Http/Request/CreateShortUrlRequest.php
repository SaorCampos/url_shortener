<?php

namespace App\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class CreateShortUrlRequest extends FormRequest
{
    public function rules()
    {
        return [
            'url' => ['required', 'url', 'max:2048']
        ];
    }
    public function authorize(): bool
    {
        return true;
    }
}
