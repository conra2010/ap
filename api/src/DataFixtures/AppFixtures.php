<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Story\DefaultPostsStory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        DefaultPostsStory::load();
    }
}
