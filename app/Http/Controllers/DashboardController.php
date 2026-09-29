<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use App\Models\Passport\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Passport\Token;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $apps = Client::query()
            ->where('revoked', false)
            ->where('show_on_dashboard', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (Client $client) => $client->hasGrantType('authorization_code') && $client->appUrl());

        $activeClientIds = $user->tokens()
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->pluck('client_id')
            ->unique()
            ->all();

        $myActivities = $user->loginActivities()->latest('created_at')->limit(5)->get();

        $admin = $user->is_admin ? $this->adminData() : null;

        return view('dashboard', compact('apps', 'activeClientIds', 'myActivities', 'admin'));
    }

    private function adminData(): array
    {
        $activeTokens = Token::query()->where('revoked', false)->where('expires_at', '>', now());

        // Login SSO per hari selama 14 hari terakhir (hari tanpa login bernilai 0).
        $from = today()->subDays(13);
        $counts = LoginActivity::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $chart = collect(range(0, 13))->map(function (int $i) use ($from, $counts) {
            $day = $from->copy()->addDays($i);

            return [
                'label' => $day->translatedFormat('d M'),
                'value' => (int) ($counts[$day->toDateString()] ?? 0),
            ];
        });

        $popularApps = Client::query()
            ->where('revoked', false)
            ->withCount(['tokens as sessions' => fn ($q) => $q->where('revoked', false)->where('expires_at', '>', now())])
            ->orderByDesc('sessions')
            ->limit(5)
            ->get(['id', 'name']);

        return [
            'stats' => [
                'users' => User::count(),
                'users_new' => User::where('created_at', '>=', now()->subDays(7))->count(),
                'users_inactive' => User::where('is_active', false)->count(),
                'clients' => Client::where('revoked', false)->count(),
                'clients_revoked' => Client::where('revoked', true)->count(),
                'sessions' => (clone $activeTokens)->count(),
                'online_users' => (clone $activeTokens)->distinct()->count('user_id'),
                'logins_today' => LoginActivity::where('created_at', '>=', today())->count(),
                'logins_yesterday' => LoginActivity::whereBetween('created_at', [today()->subDay(), today()])->count(),
            ],
            'chart' => $chart,
            'popularApps' => $popularApps,
            'recentActivities' => LoginActivity::with('user:id,name,email')->latest('created_at')->limit(8)->get(),
        ];
    }
}
