<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationVerificationCode;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $flow = $request->query('flow') === 'register' ? 'register' : 'login';
        $clientId = config('services.google.client_id');

        if (! $clientId || ! config('services.google.client_secret')) {
            return redirect('/'.$flow.'?oauth_error=google_not_configured');
        }

        $state = Str::random(48);
        $request->session()->put([
            'google_oauth_state' => $state,
            'google_oauth_flow' => $flow,
            'google_oauth_redirect' => $this->validPostAuthPath($request->query('redirect')),
        ]);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $flow = $request->session()->pull('google_oauth_flow', 'login');
        $errorPath = $flow === 'register' ? '/register' : '/login';
        $intendedPath = $request->session()->pull('google_oauth_redirect');
        $expectedState = $request->session()->pull('google_oauth_state');
        $receivedState = (string) $request->query('state', '');

        if (! $expectedState || ! hash_equals($expectedState, $receivedState)) {
            return redirect($errorPath.'?oauth_error=invalid_state');
        }

        if ($request->query('error') || ! $request->query('code')) {
            return redirect($errorPath.'?oauth_error=cancelled');
        }

        try {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $request->query('code'),
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect_uri'),
                'grant_type' => 'authorization_code',
            ])->throw()->json();

            $profile = Http::withToken($tokenResponse['access_token'] ?? '')
                ->acceptJson()
                ->get('https://openidconnect.googleapis.com/v1/userinfo')
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            report($exception);

            return redirect($errorPath.'?oauth_error=unavailable');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect($errorPath.'?oauth_error=failed');
        }

        $email = Str::lower(trim((string) ($profile['email'] ?? '')));
        $googleId = (string) ($profile['sub'] ?? '');

        if (! $email || ! $googleId || ! filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return redirect($errorPath.'?oauth_error=unverified_email');
        }

        $user = DB::transaction(function () use ($email, $googleId, $profile): User|false|null {
            $user = User::where('google_id', $googleId)->orWhere('email', $email)->lockForUpdate()->first();

            if ($user && $user->google_id && $user->google_id !== $googleId) {
                return false;
            }

            if (! $user) {
                return null;
            }

            $user->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            DB::table('pending_email_verifications')->where('email', $email)->delete();

            return $user;
        });

        if ($user === false) {
            return redirect($errorPath.'?oauth_error=account_conflict');
        }

        if (! $user) {
            $code = (string) random_int(100000, 999999);
            DB::table('pending_email_verifications')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => trim((string) ($profile['name'] ?? '')) ?: Str::before($email, '@'),
                    'password' => Hash::make(Str::random(64)),
                    'google_id' => $googleId,
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            try {
                Mail::to($email)->send(new RegistrationVerificationCode($code));
            } catch (\Throwable $exception) {
                report($exception);

                return redirect('/register?verify_email='.urlencode($email).'&oauth_error=otp_delivery_failed');
            }

            return redirect('/register?verify_email='.urlencode($email).'&oauth_notice=otp_sent');
        }

        auth()->login($user);
        $request->session()->regenerate();

        return redirect($intendedPath ?? ($user->workspaces()->wherePivot('status', 'active')->exists() ? '/app/home' : '/onboarding/workspace'));
    }

    private function validPostAuthPath(mixed $path): ?string
    {
        return is_string($path) && preg_match('#^/(invite|app|onboarding)/#', $path) === 1 ? $path : null;
    }
}
