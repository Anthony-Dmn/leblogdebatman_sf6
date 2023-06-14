<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Recaptcha\RecaptchaValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{

    /**
     * Contrôleur de la page d'inscription
     */
    #[Route('/creer-un-compte/', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, RecaptchaValidator $recaptcha): Response
    {

        // Si l'utilisateur est déjà connecté, on le redirige sur l'accueil
        if($this->getUser()){
            return $this->redirectToRoute('main_home');
        }

        // Création d'un nouvel utilisateur
        $user = new User();

        // Création d'un nouveau formulaire de création de compte
        $form = $this->createForm(RegistrationFormType::class, $user);

        // Remplissage du formulaire avec les données POST (dans $request)
        $form->handleRequest($request);

        // Si le formulaire a été envoyé
        if ($form->isSubmitted()) {

            // Récupération de la valeur du captcha ( $_POST['g-recaptcha-response'] )
            $captchaResponse = $request->request->get('g-recaptcha-response', null);

            // Récupération de l'adresse IP de l'utilisateur ( $_SERVER['REMOTE_ADDR'] )
            $ip = $request->server->get('REMOTE_ADDR');

            // Si le captcha est null ou s'il est invalide, ajout d'une erreur générale sur le formulaire (qui sera considéré comme échoué après)
            if($captchaResponse == null || !$recaptcha->verify($captchaResponse, $ip)){

                // Ajout d'une nouvelle erreur dans le formulaire
                $form->addError( new FormError('Veuillez remplir le captcha de sécurité') );
            }

            // Si le formulaire n'a pas d'erreur
            if($form->isValid()) {

                // Hydratation du mot de passe hashé, en utilisant le service de hashage
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Hydratation de la date d'inscription du nouvel utilisateur
                $user->setRegistrationDate(new \DateTime());

                // Sauvegarde en base de données du nouveau compte grâce au manager général des entités
                $entityManager->persist($user);
                $entityManager->flush();


                // Message flash de succès
                $this->addFlash('success', 'Votre compte a bien été créé avec succès !');

                // Redirection de l'utilisateur vers la page de connexion
                return $this->redirectToRoute('app_login');
            }
        }

        // Appel en la vue en lui envoyant le formulaire à afficher
        return $this->render('registration/register.html.twig', [
            'registration_form' => $form->createView(),
        ]);
    }

}
