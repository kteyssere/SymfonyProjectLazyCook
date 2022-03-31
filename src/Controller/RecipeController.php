<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Commentary;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\CommentaryType;
use App\Form\RecipeType;
use App\Repository\CategoryRepository;
use App\Repository\CommentaryRepository;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
#[Route('/recipe')]
class RecipeController extends AbstractController
{

    /**
     * @var Security
     */
    private Security $security;
    private CategoryRepository $categoryRepository;
    private RecipeRepository $recipeRepository;

    public function __construct(Security $security, CategoryRepository $categoryRepo, RecipeRepository $recipeRepo)
    {
        $this->security = $security;
        $this->categoryRepository = $categoryRepo;
        $this->recipeRepository = $recipeRepo;

    }

    #[Route('/', name: 'app_recipe_index', methods: ['GET'])]
    public function index(RecipeRepository $recipeRepository, Request $request): Response
    {
        $recipes = [];

        $form = $this->createFormBuilder()
                ->setMethod('GET')
            ->add('query', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez un mot-clé'
                ]
            ])
            ->add('difficulty', ChoiceType::class, [
                'required' => false,
                'choices'  => [
                    'Facile' => 'Facile',
                    'Moyen' => 'Moyen',
                    'Difficile' => 'Difficile',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'required' => false,
                'choices' => $this->categoryRepository->findAll(),
                'choice_value' => 'id',
                'choice_label' => function(?Category $category){
                    return $category ? $category->getName() : '';
                },
            ])
            ->add('numperson', NumberType::class, [
                'required' => false,
            ])
            ->add('time', NumberType::class, [
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $query = $form->getData()['query'];
            $difficulty = $form->getData()['difficulty'];
            $nbperson = $form->getData()['numperson'];
            $time = $form->getData()['time'];
            $category = $form->getData()['category'];

            if($query) {
                $recipes = $recipeRepository->findRecipeByName($query);
            } else if($difficulty) {
                $recipes = $recipeRepository->findRecipeByDiff($difficulty);
            } else if($nbperson) {
                $recipes = $recipeRepository->findRecipeByNbPerson($nbperson);
            } else if($time) {
                $recipes = $recipeRepository->findRecipeByTime($time);
            } else if($category){
                $recipes = $recipeRepository->findRecipeByCategory($category);
            } else {
                $recipes = $recipeRepository->findAll();
            }
        } else {
            $recipes = $recipeRepository->findAll();
        }

        return $this->render('recipe/index.html.twig', [
            'recipes' => $recipes,
            'form' => $form->createView()
        ]);
    }


    #[Route('/mes-recettes/{id}', name: 'my_recipe', methods: ['GET'])]
    public function allMyRecipe(RecipeRepository $recipeRepository, int $id): Response
    {
        return $this->render('recipe/myrecipe.html.twig', [
            'recipes' => $recipeRepository->findBy(['user'=>$id]),
        ]);
    }

    #[Route('/new', name: 'app_recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, RecipeRepository $recipeRepository, SluggerInterface $slugger): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipe->setDatepublire(new \DateTime('now', new \DateTimeZone('Europe/Paris')));

            /** @var User $user */
            $user = $this->security->getUser();

            $recipe->setUser($user);



            /** @var UploadedFile $pictureFile */
            $pictureFile = $form->get('picture')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $pictureFile->move(
                        'upload',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $recipe->setPicture($newFilename);
            }


            $recipeRepository->add($recipe);
            return $this->redirectToRoute('app_recipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('recipe/new.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_recipe_show', methods: ['GET'])]
    public function show(Recipe $recipe): Response
    {
        $commentary = new Commentary();
        $commentary->setRecipe($recipe);
        $form = $this->createForm(CommentaryType::class, $commentary);

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'commentary' => $recipe->getCommentaries(),
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/topdf', name: 'recipeToPdf', methods: ['GET'])]
    public function converToPdf(int $id, Request $request){

        $recipe = $this->recipeRepository->find($id);
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('pdf/index.html.twig', [
            'recipe' => $recipe,
            'httphost' => $request->getHttpHost()
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream($recipe->getName()."-by-".$recipe->getUser()->getName()."-lazycook.pdf", [
            "Attachment" => false
        ]);
    }

    #[Route('/{id}/edit', name: 'app_recipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recipe $recipe, RecipeRepository $recipeRepository, SluggerInterface $slugger): Response
    {
        $pic = $recipe->getPicture();

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $pictureFile */
            $pictureFile = $form->get('picture')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $pictureFile->move(
                        'upload',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $recipe->setPicture($newFilename);
            } else {
                $recipe->setPicture($pic);
            }

            $recipeRepository->add($recipe);
            return $this->redirectToRoute('app_recipe_show', ['id'=>$recipe->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, RecipeRepository $recipeRepository, CommentaryRepository $commentaryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->request->get('_token'))) {
            if(!$recipe->getCommentaries()->isEmpty()){
                foreach ($recipe->getCommentaries() as $item) {
                    $commentaryRepository->remove($item);
                }
            }
            $recipeRepository->remove($recipe);
        }

        return $this->redirectToRoute('app_recipe_index', [], Response::HTTP_SEE_OTHER);
    }



}
