<?php

namespace App\Controller;

use App\Form\UserProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountController extends AbstractController
{
    /**
     * @Route("/profile", name="user_profile")
     *
     * On peut limiter l'accès à une route (ou un controlleur)
     * @IsGranted("ROLE_USER")
     */
    public function index(Request $request, EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        // sauvegarde de l'adresse email en cas d'errerur
        $email = $this->getUser()->getEmail();

        // on peut récupérer l'utilisateur actuellement connecté avec $this->getUser()
        $form = $this->createForm(UserProfileFormType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération du champ plainPassword
            $password = $form->get('plainPassword')->getData();

            // On met à jour le mot de passe seulement si le champ a été remplis
            if ($password!== null) {
                $hash = $encoder->encodePassword($this->getUser(), $password);
                $this->getUser()->setPassword($hash);
            }

            $em->flush();
            $this->addFlash('success', 'Vos informations sont à jour.');

        } else {
            /*
             * On remet l'adresse email originale de l'utilisateur
             * pour éviter qu'il soit déconnecté
             */
            $this->getUser()->setEmail($email);
        }

        return $this->render('account/index.html.twig', [
            'profile_form' => $form->createView(),
        ]);
    }
}
