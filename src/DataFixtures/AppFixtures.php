<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Advice;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher){
        $this->passwordHasher = $passwordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        //création de 2 users (user et admin)
        $user = new User();
        $user->setEmail("user@ecogarden.com");
        $user->setPassword($this->passwordHasher->hashPassword($user,"password123"));
        $user->setRoles(["ROLE_USER"]);
        $manager->persist($user);

        $admin = new User();
        $admin->setEmail("admin@ecogarden.com");
        $admin->setPassword($this->passwordHasher->hashPassword($admin,"password123"));
        $admin->setRoles(["ROLE_ADMIN"]);
        $manager->persist($admin);

        // créations de plusieurs conseils
        for ($i = 0; $i < 30; $i++) {
            $advice = new Advice();
            $advice->setText("Lorem Ipsum". $i);
            $advice->setMonth(random_int(1,12));
            $advice->addCreatedBy($i%2 == 0 ? $user : $admin); //on attribut un auteur
            $manager->persist($advice);
        }

        $manager->flush();
    }
}
