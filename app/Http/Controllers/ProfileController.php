<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request)
    {
        return view('profile.show', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'surname' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'patronymic' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'number' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill($data);
        $user->save();

        return redirect()
            ->route('profile.show')
            ->with('profileUpdated', __('Данные профиля успешно обновлены.'));
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return redirect()
            ->route('profile.show')
            ->with('passwordUpdated', __('Пароль успешно обновлён.'));
    }
}
