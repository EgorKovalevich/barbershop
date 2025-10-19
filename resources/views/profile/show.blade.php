@extends('layouts.main')

@section('content')
    <div class="relative isolate min-h-screen bg-slate-950">
        <div class="absolute inset-x-0 top-0 -z-10 overflow-hidden">
            <div class="h-96 bg-gradient-to-b from-amber-500/20 via-yellow-400/10 to-transparent blur-3xl"></div>
        </div>

        <div class="wrapper">
            <div class="flex flex-col gap-8 py-12 sm:py-16">
                <div class="mx-auto w-full max-w-5xl rounded-3xl border border-white/10 bg-white/5 px-6 py-10 shadow-[0_40px_80px_-40px_rgba(15,23,42,0.65)] backdrop-blur">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-white/60">{{ __('Панель профиля') }}</p>
                            <h1 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $user?->surname }} {{ $user?->name }}</h1>
                            <p class="mt-2 max-w-xl text-sm text-white/60">Просматривайте и управляйте информацией об аккаунте в интерфейсе, вдохновлённом Filament.</p>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-600 text-3xl font-semibold uppercase text-white shadow-lg">
                                @if ($user?->avatar_url ?? false)
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user?->name ?? $user?->email }}" class="h-full w-full object-cover">
                                @else
                                    {{ collect([$user?->surname, $user?->name])
                                        ->filter()
                                        ->map(fn ($part) => mb_substr($part, 0, 1))
                                        ->join('') ?: ($user?->email ? mb_substr($user->email, 0, 1) : '—') }}
                                @endif
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-white/80">
                                <p class="text-xs uppercase tracking-[0.2em] text-white/50">Статус</p>
                                <p class="mt-2 text-sm font-semibold">
                                    {{ $user?->role === 'admin' ? 'Администратор Filament' : 'Клиент' }}
                                </p>
                                <p class="mt-1 text-xs text-white/50">На платформе с {{ optional($user?->created_at)->format('d.m.Y') ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mx-auto grid w-full max-w-5xl grid-cols-1 gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-7">
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_30px_60px_-40px_rgba(15,23,42,0.6)] backdrop-blur">
                            <h2 class="text-lg font-semibold text-white">Основная информация</h2>
                            <p class="mt-1 text-sm text-white/50">Ваша персональная информация отображается в виде карточек, как в Filament.</p>

                            <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4 shadow-inner">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-white/40">Фамилия</dt>
                                    <dd class="mt-2 text-base font-medium text-white">{{ $user?->surname ?? '—' }}</dd>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4 shadow-inner">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-white/40">Имя</dt>
                                    <dd class="mt-2 text-base font-medium text-white">{{ $user?->name ?? '—' }}</dd>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4 shadow-inner">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-white/40">Отчество</dt>
                                    <dd class="mt-2 text-base font-medium text-white">{{ $user?->patronymic ?? '—' }}</dd>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4 shadow-inner">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-white/40">Email</dt>
                                    <dd class="mt-2 text-base font-medium text-white">{{ $user?->email ?? '—' }}</dd>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4 shadow-inner">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-white/40">Телефон</dt>
                                    <dd class="mt-2 text-base font-medium text-white">{{ $user?->number ?? '—' }}</dd>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4 shadow-inner">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-white/40">Дата регистрации</dt>
                                    <dd class="mt-2 text-base font-medium text-white">{{ optional($user?->created_at)->format('d.m.Y H:i') ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="flex h-full flex-col justify-between gap-6 rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900/80 via-slate-900/60 to-slate-900/80 p-6 shadow-[0_30px_60px_-40px_rgba(15,23,42,0.6)] backdrop-blur">
                            <div>
                                <h2 class="text-lg font-semibold text-white">Доступ к профилю</h2>
                                <p class="mt-1 text-sm text-white/50">Следите за актуальностью контактных данных и безопасностью аккаунта.</p>
                            </div>

                            <div class="space-y-4">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-white/80">
                                    <p class="text-xs uppercase tracking-[0.2em] text-white/50">Последнее обновление</p>
                                    <p class="mt-2 text-base font-semibold text-white">{{ optional($user?->updated_at)->diffForHumans() ?? '—' }}</p>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-white/80">
                                    <p class="text-xs uppercase tracking-[0.2em] text-white/50">Email для входа</p>
                                    <p class="mt-2 text-base font-semibold text-white">{{ $user?->email ?? '—' }}</p>
                                </div>

                                <a href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('profile-logout-form').submit();"
                                   class="group flex items-center justify-between rounded-2xl bg-gradient-to-r from-rose-500/90 to-red-500/90 px-5 py-4 text-sm font-semibold text-white shadow-lg transition hover:from-rose-500 hover:to-red-500">
                                    <span>Выйти из аккаунта</span>
                                    <svg class="h-5 w-5 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h8.69l-2.22-2.22a.75.75 0 111.06-1.06l3.5 3.5a.75.75 0 010 1.06l-3.5 3.5a.75.75 0 01-1.06-1.06l2.22-2.22H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                                <form id="profile-logout-form" class="hidden" method="POST" action="{{ route('logout') }}">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
