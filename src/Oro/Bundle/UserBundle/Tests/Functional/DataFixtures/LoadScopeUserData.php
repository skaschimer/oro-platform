<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeUserData extends AbstractFixture implements DependentFixtureInterface
{
    const SIMPLE_USER_SCOPE = 'simple_user_scope';

    #[\Override]
    public function getDependencies()
    {
        return [
          LoadUserData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $scope = new Scope();
        $scope->setUser($this->getReference(LoadUserData::SIMPLE_USER));
        $manager->persist($scope);
        $manager->flush();
        $this->setReference(self::SIMPLE_USER_SCOPE, $scope);
    }
}
