<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\DeviceSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Cookie;

class AuthenticateViaDeviceSession
{
    public function handle(Request $request, Closure $next)
    {
        // If already authenticated, update last_seen and continue
        if ($request->user()) {
            $this->touchSessionIfExists($request);
            return $next($request);
        }

        $cookieName = 'member_device_token';
        $plainToken = $request->cookie($cookieName);
        $ip = $request->ip();
        $ua = (string) $request->userAgent();

        // 1) If cookie exists, try to match a session by hashed token
        if ($plainToken) {
            $session = DeviceSession::where('revoked', false)
                ->where('ip_address', $ip)
                ->get()
                ->first(function ($s) use ($plainToken) {
                    return $s && Hash::check($plainToken, $s->hashed_token);
                });

            if ($session && $session->isActive()) {
                // authenticate the member
                Auth::loginUsingId($session->member_id);

                // update last_seen
                $session->update(['last_seen' => Carbon::now()]);

                return $next($request);
            }
        }

        // 2) Cookie absent or not matching — try to find an active session by IP (and optionally UA)
        $sessionByIp = DeviceSession::where('ip_address', $ip)
            ->where('revoked', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->orderByDesc('last_seen')
            ->first();

        if ($sessionByIp && $sessionByIp->isActive()) {
            // Optionally require some minimal UA match (e.g., same browser family)
            // $sameUa = Str::substr($sessionByIp->user_agent, 0, 40) === Str::substr($ua, 0, 40);

            // For improved security uncomment UA check above and require $sameUa true.

            // rotate / renew token: create a new plain token, store hashed, update session, set cookie
            $newPlain = hash('sha256', $sessionByIp->member_id . '|' . Str::random(60) . '|' . time());
            $sessionByIp->update([
                'hashed_token' => Hash::make($newPlain),
                'last_seen' => Carbon::now(),
                'ip_address' => $ip,
                'user_agent' => $ua,
                'expires_at' => Carbon::now()->addDays(30),
            ]);

            // authenticate
            Auth::loginUsingId($sessionByIp->member_id);

            // attach cookie to response after handling the request using a header trick:
            $response = $next($request);

            $cookie = Cookie::create($cookieName, $newPlain, $sessionByIp->expires_at->getTimestamp())
                ->withHttpOnly(true)
                ->withSecure(app()->environment('production'))
                ->withSameSite('lax');

            return $response->withCookie($cookie);
        }

        // nothing found — continue unauthenticated
        return $next($request);
    }

    protected function touchSessionIfExists(Request $request)
    {
        $cookieName = 'member_device_token';
        $plainToken = $request->cookie($cookieName);
        $ip = $request->ip();

        if (! $plainToken) return;

        $session = DeviceSession::where('member_id', $request->user()->id)
            ->where('ip_address', $ip)
            ->get()
            ->first(function ($s) use ($plainToken) {
                return $s && \Illuminate\Support\Facades\Hash::check($plainToken, $s->hashed_token);
            });

        if ($session) {
            $session->update(['last_seen' => Carbon::now()]);
        }
    }
}
