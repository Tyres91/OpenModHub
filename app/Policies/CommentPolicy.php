<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Mod;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user, Mod $mod): bool
    {
        return $mod->isApproved();
    }

    public function moderate(User $user, Comment $comment): bool
    {
        return $user->hasRole('admin') || $user->hasRole('editor');
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->hasRole('admin') || $user->hasRole('editor');
    }
}
