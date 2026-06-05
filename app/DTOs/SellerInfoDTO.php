<?php

namespace App\DTOs;

use App\Models\V4User;

class SellerInfoDTO
{
    public int $id;
    public ?string $name;
    public ?string $username;
    public ?string $profile_photo;
    public ?string $city;
    public ?string $state;
    public ?string $country;
    public ?string $role;

    public function __construct(
        int $id,
        ?string $name,
        ?string $username,
        ?string $profile_photo,
        ?string $city,
        ?string $state,
        ?string $country,
        ?string $role
    ) {
        $this->id            = $id;
        $this->name          = $name;
        $this->username      = $username;
        $this->profile_photo = $profile_photo;
        $this->city          = $city;
        $this->state         = $state;
        $this->country       = $country;
        $this->role          = $role;
    }

    public static function fromUser(V4User $user): self
    {
        return new self(
            $user->id,
            trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: null,
            $user->username,
            $user->profile_photo,
            $user->city,
            $user->state,
            $user->country,
            $user->role
        );
    }

    /** Columns to select when eager-loading the user relation. */
    public static function selectColumns(): string
    {
        return 'id,first_name,last_name,username,profile_photo,city,state,country,role';
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'username'      => $this->username,
            'profile_photo' => $this->profile_photo,
            'city'          => $this->city,
            'state'         => $this->state,
            'country'       => $this->country,
            'role'          => $this->role,
        ];
    }
}
