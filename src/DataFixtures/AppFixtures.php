<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;

class AppFixtures extends Fixture
{

    /**
     * Récupération auprès de Symfony du service de hashage des mots de passe, dont on a besoin dans nos fixtures.
     * Ce service sera utilisable via "$this->encoder"
     */
    public function __construct(private UserPasswordHasherInterface $encoder){}


    public function load(ObjectManager $manager): void
    {

        // Instanciation du Faker (en français)
        $faker = Faker\Factory::create('fr_FR');

        // Création d'un compte admin
        $admin = new User();

        // Hydratation du compte admin
        $admin
            ->setEmail('a@a.a')
            ->setRegistrationDate( $faker->dateTimeBetween('-1 year', 'now') )
            ->setPseudonym('Batman')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword(
                $this->encoder->hashPassword($admin, 'aaaaaaaaA7/')
            )
        ;

        // Persistance du compte admin
        $manager->persist($admin);


        // Création de 10 comptes utilisateur (avec une boucle)
        for($i = 0; $i < 10; $i++){

            // Création d'un nouveau compte utilisateur
            $user = new User();

            // Hydratation du compte avec des données aléatoires
            $user
                ->setEmail( $faker->email )
                ->setRegistrationDate( $faker->dateTimeBetween('-1 year', 'now') )
                ->setPseudonym( $faker->userName )
                ->setPassword(
                    $this->encoder->hashPassword($admin, 'aaaaaaaaA7/')
                )
            ;

            // Persistance du compte
            $manager->persist($user);

            // Stockage du compte de côté pour créer des commentaires plus bas
            $users[] = $user;

        }

        // Création de 200 articles (avec une boucle)
        for($i = 0; $i < 200; $i++){

            // Création d'un nouvel article
            $article = new Article();

            // Hydratation de l'article avec des données aléatoires
            $article
                ->setTitle( $faker->sentence(10) )
                ->setContent( $faker->paragraph(15) )
                ->setPublicationDate( $faker->dateTimeBetween('-1 year', 'now') )
                ->setAuthor( $admin )   // Batman sera l'auteur de tous les articles fakes
            ;

            // Persistance de l'article
            $manager->persist( $article );

            // Création entre 0 et 10 commentaires aléatoires par article (avec une boucle)
            $rand = rand(0, 10);

            for($j = 0; $j < $rand; $j++){

                // Création d'un nouveau commentaire
                $newComment = new Comment();

                // Hydratation du commentaire avec des données aléatoires
                $newComment
                    ->setArticle( $article )
                    // Date aléatoire entre maintenant et il y a un an
                    ->setPublicationDate( $faker->dateTimeBetween('-1 year', 'now') )
                    // Auteur aléatoire parmi les comptes créés plus haut
                    ->setAuthor( $faker->randomElement($users) )
                    ->setContent( $faker->paragraph(5) )
                ;


                // Persistance du commentaire
                $manager->persist($newComment);

            }

        }

        // Sauvegarde des nouvelles entités dans la base de données via le manager général des entités
        $manager->flush();
    }
}
