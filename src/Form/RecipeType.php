<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Recipe;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class RecipeType extends AbstractType
{

    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $category){
        $this->categoryRepository = $category;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('picture', FileType::class, [
                'data_class' => null,
                'label' => 'Photo de la recette',
                'required' => true,
                'constraints' => [
                    new Image([
                        'maxSize' => '1024k',
                    ])
                ],
            ])
            ->add('name')
            ->add('category', ChoiceType::class, [
                'choices' => $this->categoryRepository->findAll(),
                'choice_value' => 'id',
                'choice_label' => function(?Category $category){
                    return $category ? $category->getName() : '';
                },
            ])
            ->add('numberOfPerson')
            ->add('preparationTime')
            ->add('difficulty', ChoiceType::class, [
                'choices'  => [
                    'Facile' => 'Facile',
                    'Moyen' => 'Moyen',
                    'Difficile' => 'Difficile',
                ],
            ])
            ->add('ingredients')
            ->add('utensils')
            ->add('content');
            //->add('user')

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
