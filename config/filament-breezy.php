<?php
return [
    "enable_profile_page" => true,
    "show_profile_page_in_user_menu" => true,
    "show_profile_page_in_navbar" => false,
    "profile_page_icon" => 'heroicon-o-document-text',
    "password_rules" => ['min:8'],
    "user_model" => config(
        "auth.providers.users.model",
        App\Models\User::class
    ),
    "users_table" => "users",
    "reset_broker" => config("auth.defaults.passwords"),
    "fallback_login_field" => "email",
    "route_group_prefix" => 'filament.auth.',
    "enable_2fa" => false,
    "password_confirmation_seconds" => config('auth.password_timeout'),
    "auth_card_max_w" => "md",
    "enable_registration" => true,
    "registration_component_path" => \App\Filament\Auth\Register::class,
    "password_reset_component_path" => \JeffGreco13\FilamentBreezy\Http\Livewire\Auth\ResetPassword::class,
    "email_verification_component_path" => \JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Verify::class,
    "email_verification_controller_path" => \JeffGreco13\FilamentBreezy\Http\Controllers\EmailVerificationController::class,
    "profile_page_component_path" => \JeffGreco13\FilamentBreezy\Pages\MyProfile::class,
    "registration_redirect_url" => config("filament.home_url", "/"),
    "enable_sanctum" => false,
    "sanctum_permissions" => ["create", "read", "update", "delete"],
];
