<?php

namespace App\Filament\Auth;

use Filament\Forms;
use JeffGreco13\FilamentBreezy\FilamentBreezy;
use JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
    public $surname;

    public $patronymic;

    public $number;

    public $role = 'client';

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('surname')
                ->label('Фамилия')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('name')
                ->label('Имя')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('patronymic')
                ->label('Отчество')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('number')
                ->label('Номер телефона')
                ->tel()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label(__('filament-breezy::default.fields.email'))
                ->required()
                ->email()
                ->maxLength(255)
                ->unique(table: config('filament-breezy.user_model')),
            Forms\Components\TextInput::make('password')
                ->label(__('filament-breezy::default.fields.password'))
                ->required()
                ->password()
                ->rules(app(FilamentBreezy::class)->getPasswordRules()),
            Forms\Components\TextInput::make('password_confirm')
                ->label(__('filament-breezy::default.fields.password_confirm'))
                ->required()
                ->password()
                ->same('password'),
        ];
    }

    protected function prepareModelData($data): array
    {
        return array_merge(parent::prepareModelData($data), [
            'surname' => $data['surname'],
            'patronymic' => $data['patronymic'],
            'number' => $data['number'] ?? null,
            'role' => $this->role ?? 'client',
        ]);
    }
}
