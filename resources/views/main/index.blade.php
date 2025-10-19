@extends('layouts.main')

@section('content')
<div class="services" id="services">
    <div class="wrapper">
        <div class="box_services">
            <div class="header_services">
                <h2>УСЛУГИ | ЦЕНЫ</h2>
            </div>

            @foreach ($categories->sortBy('amount') as $category)
                <div class="container_services cursor-pointer" data-modal-target="authentication-modal"
                     data-modal-toggle="authentication-modal">
                    <div class="strings_services">
                        <h3 class="uppercase">{{ $category->name }}</h3>
                    </div>
                    <div class="strings_price">
                        <p>{{ $category->amount }} Br</p>
                    </div>
                </div>
            @endforeach

            <div id="authentication-modal" tabindex="-1" aria-hidden="true"
                 class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-modal md:h-full">
                <div class="relative w-full h-full max-w-md md:h-auto">
                    <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">

                        <button type="button"
                                class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white"
                                data-modal-hide="authentication-modal">
                            <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>

                        <div class="px-6 py-6 lg:px-8">
                            <h3 class="mb-4 text-xl font-medium text-gray-900 dark:text-white text-center">Запись</h3>
                            @php
                                $authUser = auth()->user();
                                $defaultName = $authUser
                                    ? trim(collect([$authUser->surname ?? null, $authUser->name ?? null, $authUser->patronymic ?? null])->filter()->join(' '))
                                    : null;
                            @endphp
                            <form class="space-y-6" method="post" action="{{ url('store-form') }}">
                                @csrf
                                <div>
                                    <label for="subject"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Введите свое имя</label>
                                    <input type="text" name="subject" id="subject" value="{{ old('subject', $defaultName) }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                                           placeholder="Имя" required>
                                </div>

                                <div>
                                    <label for="number"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Введите свой номер</label>
                                    <input type="text" name="number" id="number" value="{{ old('number', optional($authUser)->number) }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                                           placeholder="+375(33)3333-333" required>
                                </div>

                                <div>
                                    <label for="category"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Выберите вариант стрижки</label>
                                    <select
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                                        id="category" name="category" required>
                                        @foreach ($categories as $category)
                                            @if (old('category') == $category->id)
                                                <option value="{{ $category->id }}" selected>{{ $category->name }}
                                                </option>
                                            @else
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="barber"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Выберите барбера</label>
                                    <select
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                                        id="barber" name="barber" required>
                                        @foreach ($barbers as $barber)
                                            <option value="{{ $barber->id }}">
                                                {{ $barber->surname }} {{ $barber->name }} {{ $barber->patronymic }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="start"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Выберите день</label>
                                    <select id="start" name="start"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                                        @foreach ($days as $day)
                                            <option value="{{ \Carbon\Carbon::parse($day)->toDateString() }}">
                                                {{ \Carbon\Carbon::parse($day)->translatedFormat('d/m - l') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="startTimeDiv">
                                    <label for="startTime"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Выберите время</label>
                                    <select id="startTime" name="startTime"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                                        <option value="" disabled selected>Выберите время</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="text"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Примечание</label>
                                    <input type="text" name="body" id="body"
                                           placeholder="Я опоздаю" value="{{ old('body') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                                </div>

                                <button type="submit"
                                        class="w-full text-white bg-yellow-500 hover:bg-yellow-800 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-yellow-600 dark:hover:bg-yellow-700 dark:focus:ring-yellow-800">
                                    Записаться
                                </button>

                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
