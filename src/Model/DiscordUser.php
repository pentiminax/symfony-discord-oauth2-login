<?php

namespace App\Model;

class DiscordUser
{
    public string $id;

    public string $username;

    public string $avatar;

    public ?string $bannerColor;

    public ?int $accentColor;

    public ?string $email;
}