<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Create or update a user, including optional profile-picture handling and
 * role syncing. Extracted from UserController::storeUser/updateUser so the
 * logic is shared and testable.
 */
class SaveUser
{
    /**
     * @param  array<string,mixed>  $attributes  name, username, email, designation, tel_no, ic_no, address
     * @param  array<int,string>    $roles       spatie role names to sync
     */
    public function handle(
        User $user,
        array $attributes,
        array $roles,
        ?UploadedFile $profilePicture = null,
        ?string $defaultPassword = null
    ): User {
        $user->fill($attributes);

        if ($defaultPassword !== null && ! $user->exists) {
            $user->password = Hash::make($defaultPassword);
        }

        // Persist first so a new user has an id for the filename.
        $user->save();

        if ($profilePicture) {
            $fileName = 'profile_pic_'.$user->id.'.'.$profilePicture->getClientOriginalExtension();
            $path = $profilePicture->storeAs('public/images/users', $fileName);
            $user->profile_path = (string) Str::of($path)->replace('public', 'storage');
            $user->save();
        }

        $user->syncRoles($roles);

        return $user;
    }
}
