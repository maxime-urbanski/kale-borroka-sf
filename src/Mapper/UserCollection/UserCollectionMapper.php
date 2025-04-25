<?php

declare(strict_types=1);

namespace App\Mapper\UserCollection;

use App\Entity\User;
use App\Entity\UserCollection;

class UserCollectionMapper
{
    public function mapDataToUserCollectionResource(User $user): UserCollection
    {
        $userCollection = new UserCollection();
        $userCollection->setUserCollection($user);
    }
}
