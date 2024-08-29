<?php

namespace App\Tests\Seeder;

use Doctrine\Persistence\ObjectManager;

class SeederBase
{
    /**
     * @var \Doctrine\Persistence\ObjectManager
     */
    protected ObjectManager $objectManager;

    public function __construct(ObjectManager $manager)
    {
        $this->objectManager = $manager;
    }
}
