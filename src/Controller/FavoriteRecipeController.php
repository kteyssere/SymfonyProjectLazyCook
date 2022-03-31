<?php

namespace App\Controller;

use App\Entity\FavoriteRecipe;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\FavoriteRecipeType;
use App\Repository\FavoriteRecipeRepository;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

#[Route('/favorite/recipe')]
class FavoriteRecipeController extends AbstractController
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/all', name: 'all_my_favorite_recipe', methods: ['GET'])]
    public function allMyFav(FavoriteRecipeRepository $favoriteRecipeRepository): Response
    {
        /** @var User $user */
       $user = $this->security->getUser();

        $favorite = $favoriteRecipeRepository->findBy([
            'user' => $user
        ]);

        return $this->renderForm('favorite_recipe/all_my_fav.html.twig', [
            'favorite_recipes' => $favorite,
        ]);
    }

    #[Route('/new/{id}', name: 'app_favorite_recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, FavoriteRecipeRepository $favoriteRecipeRepository, Recipe $recipe, NotifierInterface $notifier, RecipeRepository $recipeRepository): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $favorite = $favoriteRecipeRepository->findOneBy([
            'recipe' => $recipe,
            'user' => $this->getUser()
        ]);

        if (!$favorite) {
            $favorite = new FavoriteRecipe();
            $favorite
                ->setRecipe($recipe)
                ->setUser($user);
            $favoriteRecipeRepository->add($favorite);
            $recipe->setLikes($recipe->getLikes()+1);
            $recipeRepository->add($recipe);
            $notifier->send(new Notification('Cette recette a bien été ajoutée à vos favoris', ['browser']));
        } else {
            $favoriteRecipeRepository->remove($favorite);
            if($recipe->getLikes()>0){
                $recipe->setLikes($recipe->getLikes()-1);
            }
            $recipeRepository->add($recipe);
            $notifier->send(new Notification('Cette recette a bien été supprimé de vos favoris', ['browser']));
        }

        return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()], Response::HTTP_SEE_OTHER);

    }

}
