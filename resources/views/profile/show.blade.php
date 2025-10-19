@extends('layouts.main')

@section('content')
    <div class="w-full bg-gray-100 py-12">
        <div class="mx-auto w-full max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-8 shadow-lg">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-900">Профиль</h1>
                    <span class="text-sm text-gray-500">Последнее обновление: {{ $user->updated_at?->translatedFormat('d.m.Y H:i') }}</span>
                </div>

                <dl class="mt-8 divide-y divide-gray-200">
                    <div class="flex flex-col gap-1 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500">Фамилия</dt>
                        <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user->surname }}</dd>
                    </div>

                    <div class="flex flex-col gap-1 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500">Имя</dt>
                        <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user->name }}</dd>
                    </div>

                    <div class="flex flex-col gap-1 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500">Отчество</dt>
                        <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user->patronymic }}</dd>
                    </div>

                    <div class="flex flex-col gap-1 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500">Электронная почта</dt>
                        <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user->email }}</dd>
                    </div>

                    @if ($user->number)
                        <div class="flex flex-col gap-1 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                            <dt class="text-sm font-medium text-gray-500">Номер телефона</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2">{{ $user->number }}</dd>
                        </div>
                    @endif

                    <div class="flex flex-col gap-1 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500">Роль</dt>
                        <dd class="text-sm capitalize text-gray-900 sm:col-span-2">{{ $user->role }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
