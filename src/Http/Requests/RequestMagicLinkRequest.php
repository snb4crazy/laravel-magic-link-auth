<?php

namespace Snb4crazy\LaravelMagicLinkAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestMagicLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
