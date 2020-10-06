<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleType;
use App\Form\ConfirmationType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/article", name="admin_article_")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/", name="list")
     */
    public function index(ArticleRepository $repository)
    {
        return $this->render('admin/article/index.html.twig', [
            'articles' => $repository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="add")
     */
    public function add(Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(ArticleType::class);
        /*
         * handleRequest permet au formulaire de récupérer
         * les données POST et de procéder à la validation
         */
        $form->handleRequest($request);

        // si le formulaire est valide on enregistre
        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * getData() permet de récupérer les données de
             * formulaire elle retourne par défaut au tableau des
             * champs du formulaire ou il retourne un objet de la
             * classe a laquelle il est lié
             */
            /** @var Article $article */
            $article = $form->getData();

            $em->persist($article);
            $em->flush();

            // ajouter un message flash
            $this->addFlash('success', 'L\'article a été créé');
            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId()
            ]);
        }

        return $this->render('admin/article/add.html.twig', [
            'article_form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit")
     */
    public function edit(Article $article, Request $request, EntityManagerInterface $em)
    {
        /**
         * Pn peut pré-remplir un formulaire en passant un 2e argument à createForm
         * On passe un tableau associatif ou un objet si le formulaire est lié
         * à une classe
         */
        $form = $this->createForm(ArticleType::class, $article);
        // Le formulaire va directement modifier l'objet
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * On a pas besoin d'appeler $form->getData()
             *      -> l'objet $article est directement modifié par le formulaire
             * On a pas besoin d'appeler $em->persist()
             *      -> Doctrine connait déjà cet objet (il existe en base de données)
             *          il sera automatique
             */
            $em->flush();
            $this->addFlash('success', 'Article mis à jour.');
        }
        return $this->render('admin/article/edit.html.twig', [
            'article' => $article,
            'article_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="delete")
     * On récupère les arguments par "autowiring" (auto cablage)
     *  -> Symfony lit notre code pour nous envoyer les arguments demandés
     */
    public function delete(Article $article, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(ConfirmationType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            // supprimer
            $em->remove($article);
            // exécuter
            $em->flush();

            /**
             * sprintf() sert à formatter une chaine de caratères
             * le %s est un emplacement pour une chaine de caractères
             */
            $this->addFlash('info', sprintf('L\'article "%s" a été supprimé.', $article->getTitle()));
            return $this->redirectToRoute('admin_article_list');
        }

        return $this->render('admin/article/delete.html.twig', [
            'delete_form' => $form->createView(),
            'article' => $article,
        ]);
    }

    /**
     * @Route("/{id}/publish/{token}", name="publish")
     * le paramètre token servira à vérifier que l'action a bien été demandée par
     * l'administrateur connecté (protection contre les attaques CSRF)
     */
    public function publish(Article $article, string $token, EntityManagerInterface $em)
    {
        /**
         * On doit nommer les jetons CSRF
         * Symfony va comparer le jeton qu'il a enregistré en
         * session avec ce que l'on a récupéré dans l'adresse
         */
        if ($this->isCsrfTokenValid('article-publish', $token) === false) {
            $this->addFlash('danger', 'Le jeton est invalide.');
            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId(),
            ]);
        }

        $article->setPublishedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'L\article a été publié');
        return $this->redirectToRoute('admin_article_edit', [
            'id' => $article->getId(),
        ]);
    }
}
