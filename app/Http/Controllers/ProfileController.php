<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile information.
     */
    public function __invoke()
    {
        $user = Auth::user();

        abort_unless($user, 404);

        return view('profile.show', [
            'user' => $user,
        ]);
    }
}
