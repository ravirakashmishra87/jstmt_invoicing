<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $user = User::query()
            ->where(function ($query) use ($login) {
                $query->where('username', $login)->orWhere('email', $login);
            })
            ->first();

        if ($user && $user->isLocked()) {
            $this->logAttempt($login, false, $request);

            return back()
                ->withInput($request->only('login'))
                ->withErrors([
                    'login' => 'Account locked until '.$user->locked_until->format('d-M-Y H:i').' after 5 failed attempts.',
                ]);
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->logAttempt($login, false, $request);

            if ($user) {
                $user->failed_login_attempts = (int) $user->failed_login_attempts + 1;
                if ($user->failed_login_attempts >= 5) {
                    $user->locked_until = now()->addMinutes(15);
                    $user->failed_login_attempts = 0;
                    $user->save();

                    return back()
                        ->withInput($request->only('login'))
                        ->withErrors(['login' => 'Account locked for 15 minutes after 5 failed attempts.']);
                }
                $user->save();
            }

            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'These credentials do not match our records.']);
        }

        if (! $user->is_active) {
            $this->logAttempt($login, false, $request);

            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'This account is inactive. Contact an administrator.']);
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->logAttempt($login, true, $request);

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function logAttempt(string $username, bool $successful, Request $request): void
    {
        DB::table('login_attempts')->insert([
            'username' => $username,
            'ip_address' => $request->ip(),
            'successful' => $successful,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
