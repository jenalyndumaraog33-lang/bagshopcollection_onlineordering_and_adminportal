<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:promote-admin {email}')]
#[Description('Promote an existing user to an active administrator')]
class PromoteUserToAdmin extends Command
{
    public function handle(ActivityLogger $activityLogger): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account exists for that email address.');

            return self::FAILURE;
        }

        $user->update([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
        $activityLogger->record(null, 'user.promoted_to_admin', $user, 'Promoted an account to administrator from the command line.');
        $this->info("{$user->email} is now an active administrator.");

        return self::SUCCESS;
    }
}
