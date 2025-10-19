@extends('layouts.main')

@section('content')
    <div class="w-full bg-gray-100 py-16">
        <div class="wrapper">
            <div class="mx-auto max-w-3xl overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="bg-gray-900 px-8 py-6 text-white">
                    <h1 class="text-2xl font-bold">Профиль</h1>
                    <p class="mt-1 text-sm text-gray-300">Информация об аккаунте</p>
                </div>

                <div class="divide-y divide-gray-100">
                    <dl class="divide-y divide-gray-100">
                        <div class="grid grid-cols-1 gap-4 px-8 py-5 sm:grid-cols-3">
                            <dt class="text-sm font-medium text-gray-500">Фамилия</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user?->surname ?? '—' }}</dd>
                        </div>

                        <div class="grid grid-cols-1 gap-4 px-8 py-5 sm:grid-cols-3">
                            <dt class="text-sm font-medium text-gray-500">Имя</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user?->name ?? '—' }}</dd>
                        </div>

                        <div class="grid grid-cols-1 gap-4 px-8 py-5 sm:grid-cols-3">
                            <dt class="text-sm font-medium text-gray-500">Отчество</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user?->patronymic ?? '—' }}</dd>
                        </div>

                        <div class="grid grid-cols-1 gap-4 px-8 py-5 sm:grid-cols-3">
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user?->email ?? '—' }}</dd>
                        </div>

                        <div class="grid grid-cols-1 gap-4 px-8 py-5 sm:grid-cols-3">
                            <dt class="text-sm font-medium text-gray-500">Телефон</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user?->number ?? '—' }}</dd>
                        </div>

                        <div class="grid grid-cols-1 gap-4 px-8 py-5 sm:grid-cols-3">
                            <dt class="text-sm font-medium text-gray-500">Роль</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2">
                                {{ $user?->role === 'admin' ? 'Администратор' : 'Клиент' }}
                            </dd>
                        </div>

                        <div class="grid grid-cols-1 gap-4 px-8 py-5 sm:grid-cols-3">
                            <dt class="text-sm font-medium text-gray-500">Дата регистрации</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2">
                                {{ optional($user?->created_at)->format('d.m.Y H:i') ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
