<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Performance\Acl\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Role;

class LoadRoleData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load roles full_access_role
     */
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $role_template_access = new Role('ROLE_LOGIN_ACCESS');
        $role_template_access->setLabel('Log-in access role');
        $this->addReference('login_access_role', $role_template_access);
        $manager->persist($role_template_access);

        $full_access_role = new Role('ROLE_FULL_ACCESS');
        $full_access_role->setLabel('Full access role');
        $this->addReference('full_access_role', $full_access_role);
        $manager->persist($full_access_role);

        $manager->flush();
    }

    #[\Override]
    public function getOrder()
    {
        return 1;
    }
}
