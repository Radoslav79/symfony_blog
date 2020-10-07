<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Instanciation de faker
        $faker = Factory::create('fr_FR');

        // Génération de 10 catégories
        for ($i = 0; $i < 10; $i++) {
            $category = (new Category())
                ->setName($faker->unique()->word)
                ->setDescription($faker->realText())
                ;

            $manager->persist($category);

            /*
             * on va associer chaque objet à une "référence" unique
             * pour pouvoir récupérer ces objets dans d'autres
             *  classes de fixtures
             */
            $reference = 'category_' . $i;
            $this->addReference($reference, $category);
        }
        // appeler les persist() à la fin de chaque catégorie puis faire le flush() d'enregistrement à la fin
        $manager->flush();
    }
}
