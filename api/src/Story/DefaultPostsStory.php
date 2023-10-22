<?php

namespace App\Story;

use App\Factory\PostFactory;
use App\Factory\TagFactory;
use Zenstruck\Foundry\Story;

final class DefaultPostsStory extends Story
{
    public function build(): void
    {
        // TODO build your story here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#stories)
        TagFactory::createMany(128);
        PostFactory::createMany(256);
    }
}
