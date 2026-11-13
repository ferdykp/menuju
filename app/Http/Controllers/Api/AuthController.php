<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\RegistrationVerificationCode;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $code = (string) random_int(100000, 999999);

        DB::table('pending_email_verifications')->updateOrInsert(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'google_id' => null,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->sendRegistrationCode($data['email'], $code);

        return response()->json(['message' => 'Kode konfirmasi telah dikirim ke email kamu.', 'email' => $data['email']], 202);
    }

    public function verifyRegistration(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = Str::lower(trim($data['email']));

        $user = DB::transaction(function () use ($email, $data): ?User {
            $pending = DB::table('pending_email_verifications')->where('email', $email)->lockForUpdate()->first();

            if (! $pending || now()->greaterThan($pending->expires_at) || $pending->attempts >= 5) {
                return null;
            }

            if (! Hash::check($data['code'], $pending->code_hash)) {
                DB::table('pending_email_verifications')->where('email', $email)->increment('attempts');
                return null;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $pending->name,
                    'password' => $pending->password,
                    'google_id' => $pending->google_id,
                    'email_verified_at' => now(),
                ],
            );

            if ($pending->google_id && $user->google_id && $user->google_id !== $pending->google_id) {
                throw ValidationException::withMessages(['code' => 'Akun Google ini sudah terhubung ke akun MENUJU lain.']);
            }
            if ($pending->google_id && ! $user->google_id) {
                $user->forceFill(['google_id' => $pending->google_id])->save();
            }
            DB::table('pending_email_verifications')->where('email', $email)->delete();

            return $user;
        });

        if (! $user) {
            throw ValidationException::withMessages(['code' => 'Kode tidak valid, terlalu banyak dicoba, atau sudah kedaluwarsa. Kirim kode baru untuk mencoba lagi.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $this->userPayload($user)]);
    }

    public function resendRegistrationCode(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $email = Str::lower(trim($data['email']));
        $pending = DB::table('pending_email_verifications')->where('email', $email)->first();

        if (! $pending) {
            throw ValidationException::withMessages(['email' => 'Mulai pendaftaran kembali untuk menerima kode konfirmasi.']);
        }

        $code = (string) random_int(100000, 999999);
        DB::table('pending_email_verifications')->where('email', $email)->update([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'updated_at' => now(),
        ]);
        $this->sendRegistrationCode($email, $code);

        return response()->json(['message' => 'Kode konfirmasi baru telah dikirim.']);
    }

    private function sendRegistrationCode(string $email, string $code): void
    {
        Mail::to($email)->send(new RegistrationVerificationCode($code));
    }

    public function login(Request $request): JsonResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $key = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi belum cocok.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Kamu sudah keluar.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        Password::sendResetLink($request->only('email'));

        // Keep account existence private; the same response is returned either way.
        return response()->json(['message' => 'Jika email terdaftar, tautan untuk mengatur ulang kata sandi akan dikirim.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => 'Tautan tidak valid atau sudah kedaluwarsa.']);
        }

        return response()->json(['message' => 'Kata sandi berhasil diperbarui.']);
    }

    private function userPayload(?User $user): array
    {
        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'workspaces' => $user?->workspaces()
                ->wherePivot('status', 'active')
                ->select('workspaces.id', 'workspaces.name', 'workspaces.partner_one_name', 'workspaces.partner_two_name', 'workspaces.wedding_date', 'workspaces.timezone', 'workspaces.onboarding_completed_at')
                ->get()
                ->map(fn ($workspace) => [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'partner_one_name' => $workspace->partner_one_name,
                    'partner_two_name' => $workspace->partner_two_name,
                    'wedding_date' => $workspace->wedding_date?->toDateString(),
                    'timezone' => $workspace->timezone,
                    'onboarding_completed_at' => $workspace->onboarding_completed_at?->toIso8601String(),
                    'role' => $workspace->pivot->role,
                ]),
        ];
    }
}
