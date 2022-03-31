<?php

namespace App\Controller;

use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/homepage', name: 'app_homepage')]
    public function index(RecipeRepository $recipeRepository): Response
    {
        $recipesMostLiked = $recipeRepository->findByMostLiked();
        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'HomepageController',
            'recipes_most_liked'=>$recipesMostLiked,
        ]);
    }
}
