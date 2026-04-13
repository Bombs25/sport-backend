<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Ce qu’il fait : valide e-mail + code OTP + nouveau mot de passe pour `POST …/forgot-password/reset` ou `…/update`.
 *
 * Pourquoi : même exigences de mot de passe que l’inscription ; le nouveau mot de passe doit différer de l’ancien (écran RN).
 */
class ResetPasswordWithOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('email')) {
            $merge['email'] = Str::lower((string) $this->input('email'));
        }

        /*
         * Clients (ex. RapidAPI / RN) envoient parfois `passwordconfirmation` sans underscore : la règle Laravel
         * `confirmed` attend `password_confirmation`.
         */
        if (! $this->has('password_confirmation') && $this->has('passwordconfirmation')) {
            $merge['password_confirmation'] = $this->input('passwordconfirmation');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => __('La confirmation du mot de passe ne correspond pas.'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = User::query()->whereRaw('lower(email) = ?', [$this->input('email')])->first();

            if ($user !== null && Hash::check((string) $this->input('password'), $user->password)) {
                $validator->errors()->add(
                    'password',
                    __('Le nouveau mot de passe doit être différent de l’ancien.'),
                );
            }
        });
    }
}
