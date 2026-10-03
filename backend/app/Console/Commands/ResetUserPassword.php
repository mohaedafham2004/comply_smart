<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'user:set-password {email} {password=password123}';
    protected $description = 'Set or reset password for a user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Successfully updated password for {$email} to: {$password}");
        return 0;
    }
}
