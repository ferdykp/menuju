<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Kata sandi harus terdiri dari minimal 10 karakter.',
            'password.letters' => 'Kata sandi harus memuat setidaknya satu huruf.',
            'password.numbers' => 'Kata sandi harus memuat setidaknya satu angka.',
            'password.confirmed' => 'Konfirmasi kata sandi belum sama.',
        ];
    }
}
