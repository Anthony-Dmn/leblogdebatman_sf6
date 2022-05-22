<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\EditPhotoFormType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{

    /**
     * Contrôleur de la page d'accueil
     */
    #[Route('/', name: 'main_home')]
    public function home(ManagerRegistry $doctrine): Response
    {

        // Récupération des 3 derniers articles publiés (le nombre d'article dépend du paramètre configuré dans le fichier services.yaml)
        $articleRepo = $doctrine->getRepository(Article::class);

        $articles = $articleRepo->findBy([], ['publicationDate' => 'DESC'], $this->getParameter('app.article.number_of_latest_articles_on_home'));

        // Appel de la vue en lui envoyant les derniers articles publiés à afficher
        return $this->render('main/home.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Contrôleur de la page de profil
     *
     * Accès réservé aux connectés (ROLE_USER)
     */
    #[Route('/mon-profil/', name: 'main_profil')]
    #[IsGranted('ROLE_USER')]
    public function profil(): Response
    {
        return $this->render('main/profil.html.twig');
    }

    /**
     * Page de modification de la photo de profil
     *
     * Accès réservé aux connectés (ROLE_USER)
     */
    #[Route('/edit-photo/', name: 'main_edit_photo')]
    #[IsGranted('ROLE_USER')]
    public function editPhoto(Request $request, ManagerRegistry $doctrine): Response
    {

        // Création du formulaire de changement de photo
        $form = $this->createForm(EditPhotoFormType::class);

        // Liaison des données de requête (POST) avec le formulaire
        $form->handleRequest($request);

        // Si le formulaire a été envoyé et s'il ne contient pas d'erreur
        if($form->isSubmitted() && $form->isValid()){

            // Récupération du champ photo dans le formulaire
            $photo = $form->get('photo')->getData();

            // Si l'utilisateur a déjà une photo de profil et si cette photo existe, on la supprime
            if(
                $this->getUser()->getPhoto() != null &&
                file_exists( $this->getParameter('app.user.photo.directory') . $this->getUser()->getPhoto())
            ){

                // Suppression de l'ancienne photo
                unlink( $this->getParameter('app.user.photo.directory') . $this->getUser()->getPhoto() );
            }


            // Création d'un nouveau nom de fichier pour la nouvelle photo (boucle jusqu'à trouver un nom qui ne soit pas déjà utilisé)

            // NOTE : le nom de la photo est généré en calculant le hashage de d'une grande phrase aléatoire.
            // Il y a très très peu de chance que deux photos aient le même nom aléatoire, mais dans la pratique il peut exister plusieurs
            // noms différents ayant le même hashage (colision cryptographique)
            // Même si le taux de "malchance" que cela arrive est extrêment bas, ça ne coute rien de coder proprement pour
            // que ce problème ne puisse pas arriver du tout.
            do{

                // guessExtension() permet de récupérer la vrai extension du fichier, déterminée par rapport à son vrai type MIME
                $newFileName = md5( random_bytes(100) ) . '.' . $photo->guessExtension();

            } while(file_exists( $this->getParameter('app.user.photo.directory') . $newFileName ));

            // On change le nom de la photo de l'utilisateur connecté
            $this->getUser()->setPhoto($newFileName);

            // Mise à jour du nom de la photo dans la bdd
            $em = $doctrine->getManager();
            $em->flush();

            // Déplacement physique de l'image dans le dossier paramétré dans le paramètre "app.user.photo.directory" dans le fichier config/services.yaml
            $photo->move(
                $this->getParameter('app.user.photo.directory'),
                $newFileName
            );

            // Message flash de succès + redirection sur la page de profil
            $this->addFlash('success', 'Photo de profil modifiée avec succès !');
            return $this->redirectToRoute('main_profil');

        }

        // Appel de la vue en lui envoyant le formulaire à afficher
        return $this->render('main/edit_photo.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
