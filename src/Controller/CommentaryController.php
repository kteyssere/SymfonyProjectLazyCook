<?php

namespace App\Controller;

use App\Entity\Commentary;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\CommentaryType;
use App\Repository\CommentaryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/commentary')]
class CommentaryController extends AbstractController
{

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'app_commentary_index', methods: ['GET'])]
    public function index(CommentaryRepository $commentaryRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('commentary/index.html.twig', [
            'commentaries' => $commentaryRepository->findAll(),
        ]);
    }

    #[Route('/new/{id}', name: 'app_commentary_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CommentaryRepository $commentaryRepository, Recipe $recipe): Response
    {
        $commentary = new Commentary();
        $form = $this->createForm(CommentaryType::class, $commentary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$commentary->setRecipe();
            $commentary->setDatepublicom(new \DateTime('now'));
            /** @var User $user */
            $user = $this->security->getUser();
            $commentary->setRecipe($recipe);
            $commentary->setUser($user);
            $commentaryRepository->add($commentary);
            return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('commentary/new.html.twig', [
            'commentary' => $commentary,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commentary_show', methods: ['GET'])]
    public function show(Commentary $commentary): Response
    {
        return $this->render('commentary/show.html.twig', [
            'commentary' => $commentary,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commentary_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commentary $commentary, CommentaryRepository $commentaryRepository): Response
    {
        $form = $this->createForm(CommentaryType::class, $commentary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaryRepository->add($commentary);
            return $this->redirectToRoute('app_recipe_show', ['id' => $commentary->getRecipe()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('commentary/edit.html.twig', [
            'commentary' => $commentary,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commentary_delete', methods: ['POST'])]
    public function delete(Request $request, Commentary $commentary, CommentaryRepository $commentaryRepository): Response
    {
        $recipeId = $commentary->getRecipe()->getId();
        if ($this->isCsrfTokenValid('delete'.$commentary->getId(), $request->request->get('_token'))) {
            $commentaryRepository->remove($commentary);
        }

        return $this->redirectToRoute('app_recipe_show', ['id' => $recipeId], Response::HTTP_SEE_OTHER);
    }
}
