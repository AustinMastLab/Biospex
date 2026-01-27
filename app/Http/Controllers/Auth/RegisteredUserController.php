<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GroupInvite;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(?GroupInvite $invite = null): View
    {
        return view('auth.register', ['invite' => $invite]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, ?GroupInvite $invite = null): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
                function ($attribute, $value, $fail) use ($invite) {
                    if ($invite && strtolower($value) !== strtolower($invite->email)) {
                        $fail(__('The email address does not match the invitation.'));
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $user->profile()->create([
            'first_name' => $request->name,
            'last_name' => '',
            'timezone' => config('app.timezone'),
        ]);

        if ($invite) {
            $user->groups()->attach($invite->group_id);
            $invite->delete();
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
