<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Form\NewArticleFormType;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Préfixes de la route et du nom des pages de la partie blog du site
 */
#[Route('/blog', name: 'blog_')]
class BlogController extends AbstractController
{

    /**
     * Contrôleur de la page permettant de créer un nouvel article
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     */
    #[Route('/nouvelle-publication/', name: 'new_publication')]
    #[IsGranted('ROLE_ADMIN')]
    public function newPublication(Request $request, ManagerRegistry $doctrine): Response
    {

        // Création d'un nouvel article vide
        $newArticle = new Article();

        // Création d'un formulaire de création d'article, lié à l'article vide
        $form = $this->createForm(NewArticleFormType::class, $newArticle);

        // Liaison des données de requête (POST) avec le formulaire
        $form->handleRequest($request);

        // Si le formulaire est envoyé et n'a pas d'erreur
        if($form->isSubmitted() && $form->isValid()){

            // Hydratation de l'article pour la date et l'auteur
            $newArticle
                ->setAuthor($this->getUser())           // L'auteur est l'utilisateur connecté
                ->setPublicationDate( new DateTime() )  // Date actuelle
            ;

            // Sauvegarde de l'article dans la base de données via le manager général des entités
            $em = $doctrine->getManager();
            $em->persist($newArticle);
            $em->flush();

            // Message flash de type "success"
            $this->addFlash('success', 'Article publié avec succès !');

            // Redirection de l'utilisateur vers la page détaillée de l'article tout nouvellement créé
            return $this->redirectToRoute('blog_publication_view', [
                'slug' => $newArticle->getSlug(),
            ]);

        }

        return $this->render('blog/new_publication.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * Contrôleur de la page qui liste tous les articles
     */
    #[Route('/publications/liste/', name: 'publication_list')]
    public function publicationList(Request $request, PaginatorInterface $paginator, ManagerRegistry $doctrine): Response
    {

        // Récupération du numéro de la page demandée dans l'URL
        $requestedPage = $request->query->getInt('page', 1);

        // Vérification que le numéro est positif
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        // Récupération du manager général des entités
        $em = $doctrine->getManager();

        // Création d'une requête permettant de récupérer les articles (uniquement ceux de la page demandée, grâce au paginator,et non tous les articles)
        $query = $em->createQuery('SELECT a FROM App\Entity\Article a ORDER BY a.publicationDate DESC');

        // Récupération des articles
        $articles = $paginator->paginate(
            $query,             // Requête créée précedemment
            $requestedPage,     // Numéro de la page demandée
            10              // Nombre d'articles affichés par page
        );

        // Appel de la vue en envoyant les articles à afficher
        return $this->render('blog/publication_list.html.twig', [
            'articles' => $articles,
        ]);

    }


    /**
     * Contrôleur de la page permettant de voir un article en détail (via l'id et le slug dans l'url)
     *
     * Le paramétrage "ParamConverter" permet de demander à Symfony de récupérer l'article donc l'id et le slug correspondent à {id} et {slug} dans l'url
     */
    #[Route('/publication/{slug}/', name: 'publication_view')]
    public function publicationView(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        // Si l'utilisateur n'est pas connecté, appel direct de la vue en lui envoyant l'article à afficher
        // On fait ça pour éviter que le traitement du formulaire en dessous ne soit invoqué par un autre moyen même si le formulaire html est masqué
        if(!$this->getUser()){

            return $this->render('blog/publication_view.html.twig', [
                'article' => $article,
            ]);

        }

        // Création d'un commentaire vide
        $comment = new Comment();

        // Création d'un formulaire de création de commentaire, lié au commentaire vide
        $form = $this->createForm(CommentFormType::class, $comment);

        // Liaison des données de requête (POST) avec le formulaire
        $form->handleRequest($request);

        // Si le formulaire est envoyé et n'a pas d'erreur
        if($form->isSubmitted() && $form->isValid()){

            // Hydratation du commentaire
            $comment
                ->setAuthor($this->getUser())           // L'auteur est l'utilisateur connecté
                ->setPublicationDate(new DateTime())    // Date actuelle
                ->setArticle($article)                  // Lié à l'article actuellement affiché sur la page
            ;

            // Sauvegarde du commentaire en base de données via le manager général des entités
            $em = $doctrine->getManager();
            $em->persist($comment);
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Votre commentaire a été publié avec succès !');

            // Suppression des deux variables contenant le formulaire validé et le commentaire nouvellement créé (pour éviter que le nouveau formulaire soit rempli avec)
            unset($comment);
            unset($form);

            // Création d'un nouveau commentaire vide et de son formulaire lié
            $comment = new Comment();
            $form = $this->createForm(CommentFormType::class, $comment);

        }

        // Appel de la vue en lui envoyant l'article et le formulaire à afficher
        return $this->render('blog/publication_view.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);

    }


    /**
     * Contrôleur de la page affichant les résultats des recherches faites par le formulaire de recherche dans la navbar
     */
    #[Route('/recherche/', name: 'search')]
    public function search(Request $request, PaginatorInterface $paginator, ManagerRegistry $doctrine): Response
    {

        // Récupération du numéro de la page demandée dans l'url (si il existe pas, 1 sera pris à la place)
        $requestedPage = $request->query->getInt('page', 1);

        // Si la page demandée est inférieur à 1, erreur 404
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        // On récupère la recherche de l'utilisateur depuis l'url ($_GET['q'])
        $search = $request->query->get('s', '');

        // Récupération du manager général des entités
        $em = $doctrine->getManager();

        // Création d'une requête permettant de récupérer les articles pour la page actuelle, dont le titre ou le contenu contient la recherche de l'utilisateur
        $query = $em
            ->createQuery('SELECT a FROM App\Entity\Article a WHERE a.title LIKE :search OR a.content LIKE :search ORDER BY a.publicationDate DESC')
            ->setParameters([
                'search' => '%' . $search . '%',
            ])
        ;

        // Récupération des articles
        $articles = $paginator->paginate(
            $query,
            $requestedPage,
            10
        );

        // Appel de la vue en lui envoyant les articles à afficher
        return $this->render('blog/list_search.html.twig', [
            'articles' => $articles,
        ]);

    }


    /**
     * Contrôleur de la page admin servant à supprimer un article via son id passé dans l'URL
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     *
     * Le paramètre "priority: 10" est là pour être sûr que cette route soit prioritaire sur celle de la page d'affichage d'un article qui rend celle-ci inatteignable
     */
    #[Route('/publication/suppression/{id}/', name: 'publication_delete', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationDelete(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        // Si le token CSRF passé dans l'url n'est pas le token valide, message d'erreur
        if(!$this->isCsrfTokenValid('blog_publication_delete_' . $article->getId(), $request->query->get('csrf_token'))){

            // Message flash d'erreur
            $this->addFlash('error', 'Token sécurité invalide, veuillez ré-essayer.');
        } else {

            // Suppression de l'article via le manager général des entités
            $em = $doctrine->getManager();
            $em->remove($article);
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'La publication a été supprimée avec succès !');
        }

        // Redirection de l'utilisateur sur la liste des articles
        return $this->redirectToRoute('blog_publication_list');

    }


    /**
     * Contrôleur de la page permettant de modifier un article existant via son id passé dans l'url
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     *
     * Le paramètre "priority: 10" est là pour être sûr que cette route soit prioritaire sur celle de la page d'affichage d'un article qui rend celle-ci inatteignable
     */
    #[Route('/publication/modifier/{id}/', name: 'publication_edit', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationEdit(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        // Création du formulaire de modification d'article (c'est le même que le formulaire permettant de créer un nouvel article, sauf qu'il sera déjà rempli avec les données de l'article existant "$article")
        $form = $this->createForm(NewArticleFormType::class, $article);

        // Liaison des données de requête (POST) avec le formulaire
        $form->handleRequest($request);

        // Si le formulaire est envoyé et n'a pas d'erreur
        if($form->isSubmitted() && $form->isValid()){

            // Sauvegarde des changements faits dans l'article via le manager général des entités
            $em = $doctrine->getManager();
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Publication modifiée avec succès !');

            // Redirection vers la page de l'article modifié
            return $this->redirectToRoute('blog_publication_view', [
                'slug' => $article->getSlug(),
            ]);

        }

        // Appel de la vue en lui envoyant le formulaire à afficher
        return $this->render('blog/publication_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * Contrôleur de la page permettant aux admins de supprimer un commentaire
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     */
    #[Route('/commentaire/suppression/{id}/', name: 'comment_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function commentDelete(Comment $comment, Request $request, ManagerRegistry $doctrine): Response
    {

        // Si le token CSRF passé dans l'URL n'est pas valide
        if(!$this->isCsrfTokenValid('blog_comment_delete' . $comment->getId(), $request->query->get('csrf_token'))){
            $this->addFlash('error', 'Token sécurité invalide, veuillez ré-essayer.');
        } else {

            // Suppression du commentaire via le manager général des entités
            $em = $doctrine->getManager();
            $em->remove( $comment );
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Le commentaire a été supprimé avec succès !');

        }

        // Redirection de l'utilisateur sur la page détaillée de l'article auquel est/était rattaché le commentaire
        return $this->redirectToRoute('blog_publication_view', [
            'slug' => $comment->getArticle()->getSlug(),
        ]);

    }


}
