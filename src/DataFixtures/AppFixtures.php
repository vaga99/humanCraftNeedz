<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Need;
use App\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $skill1 = new Skill();
        $skill1->setLabel("Développement java");
        $manager->persist($skill1);

        $skill2 = new Skill();
        $skill2->setLabel("Management agile");
        $manager->persist($skill2);

        $skill3 = new Skill();
        $skill3->setLabel("Relation client");
        $manager->persist($skill3);

        $skill4 = new Skill();
        $skill4->setLabel("POO");
        $manager->persist($skill4);

        $need1 = new Need();
        $need1->setTitle("Manager de projet senior");
        $need1->setSummary("Nous recherchons un.e Chef.fe de Projet expérimenté.e en développement react pour rejoindre notre équipe. Sous la responsabilité d’une directrice de projet, vous serez en relation directe avec le client et collaborerez étroitement avec les membres de l’équipe fonctionnelle et technique. Ce poste est basé à Senlis (2 jours de télétravail par semaine), des déplacements fréquents à Compiègne sont à prévoir.");
        $need1->setUrl("https://www.linkedin.com");
        $need1->addSkill($skill2);
        $need1->addSkill($skill3);
        $manager->persist($need1);

        $need2 = new Need();
        $need2->setTitle("Développeur Java Junior");
        $need2->setSummary("Nous recherchons un.e développeur expérimenté.e en développement java pour rejoindre notre équipe. Sous la responsabilité d’un lead dev, vous collaborerez étroitement avec les membres de l’équipe technique composée de 5 développeurs. Ce poste est basé à Beauvais (1 jours de télétravail par semaine), des déplacements fréquents à Creil sont à prévoir.");
        $need2->setUrl("https://stackoverflow.com");
        $need2->addSkill($skill1);
        $need2->addSkill($skill4);
        $manager->persist($need2);

        $author1 = new Author();
        $author1->setName("José");
        $author1->addNeed($need1);
        $manager->persist($author1);

        $author2 = new Author();
        $author2->setName("Michelle");
        $author2->addNeed($need2);
        $manager->persist($author2);

        $manager->flush();
    }
}
