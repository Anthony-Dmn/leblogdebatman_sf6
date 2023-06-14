<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\PhotoEditFormType;
use Doctrine\Persistence\ManagerRegistry;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{

    /**
     * Contrôleur de la page d'accueil
     */
    #[Route('/', name: 'main_home')]
    public function home(ManagerRegistry $doctrine): Response
    {

        // Récupération des 3 derniers articles publiés (le nombre d'articles dépend du paramètre "app.article.number_of_latest_articles_on_home", configuré dans le fichier "services.yaml")
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
     * Accès réservé aux personnes connectées (ROLE_USER)
     */
    #[Route('/mon-profil/', name: 'main_profil')]
    #[IsGranted('ROLE_USER')]
    public function profil(): Response
    {

        // Appel de la vue
        return $this->render('main/profil.html.twig');
    }

    /**
     * Contrôleur de la page de modification de la photo de profil
     *
     * Accès réservé aux utilisateurs connectés (ROLE_USER)
     */
    #[Route('/changer-photo-de-profil/', name: 'main_edit_photo')]
    #[IsGranted('ROLE_USER')]
    public function editPhoto(Request $request, ManagerRegistry $doctrine, CacheManager $cacheManager): Response
    {

        // Création du formulaire de changement de photo
        $form = $this->createForm(PhotoEditFormType::class);

        // Liaison des données de requête (POST) avec le formulaire
        $form->handleRequest($request);

        // Si le formulaire a été envoyé et s'il ne contient pas d'erreur
        if($form->isSubmitted() && $form->isValid()){

            // Récupération du champ photo dans le formulaire
            $photo = $form->get('photo')->getData();

            // Récupération de l'utilisateur connecté
            $connectedUser = $this->getUser();

            // Récupération de l'emplacement où on sauvegardera toutes les photos de profil (paramétré dans "config/services.yaml")
            $photoLocation = $this->getParameter('app.user.photo.directory');

            // Création d'un nouveau nom pour la nouvelle photo ("user54.png" par exemple si l'utilisateur 54 a envoyé une image png)
            // $photo->guessExtension() permet de récupérer l'extension du fichier de manière sécurisée
            $newFileName = 'user' . $connectedUser->getId() . '.' . $photo->guessExtension();

            // Si l'utilisateur possède déjà une photo de profil et si cette photo existe dans le dossier, on la supprime
            if($connectedUser->getPhoto() != null && file_exists( $photoLocation . $connectedUser->getPhoto() )){

                // Suppression de l'ancienne photo dans le cache du bundle liip imagine
                $cacheManager->remove( 'images/profils/' . $connectedUser->getPhoto() );

                // Suppression de l'ancienne photo
                unlink( $photoLocation . $connectedUser->getPhoto() );

            }

            // On change le nom de la photo de l'utilisateur
            $connectedUser->setPhoto( $newFileName );

            // Sauvegarde du nom de la nouvelle photo de profil de l'utilisateur dans la base de données
            $em = $doctrine->getManager();
            $em->flush();

            // Sauvegarde de l'image dans le dossier paramétré dans le paramètre "app.user.photo.directory" dans le fichier "config/services.yaml"
            $photo->move(
                $photoLocation,
                $newFileName,
            );

            // Message flash de succès + redirection sur la page de profil
            $this->addFlash('success', 'Photo de profil modifiée avec succès !');
            return $this->redirectToRoute('main_profil');

        }

        // Appel de la vue en lui envoyant le formulaire à afficher
        return $this->render('main/photo_edit.html.twig', [
            'photo_edit_form' => $form->createView(),
        ]);
    }

}
