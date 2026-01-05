<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Http\Requests\RegisterRequest;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Create a newly registered user.
     */
    public function create(array $input): User
    {
        /** @var RegisterRequest $request */
        $request = app(RegisterRequest::class);

        $validated = $request->validated();

        return User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
    }
}
