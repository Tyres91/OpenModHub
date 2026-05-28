<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Mod;
use App\Models\User;

class CommentPolicy
{
    public function create(User $_user, Mod $mod): bool
    {
        return $mod->isApproved();
    }

    public function moderate(User $user, Comment $_comment): bool
    {
        return $user->hasPermission('moderate_comments');
    }

    public function delete(User $user, Comment $_comment): bool
    {
        return $user->hasPermission('moderate_comments');
    }
}
