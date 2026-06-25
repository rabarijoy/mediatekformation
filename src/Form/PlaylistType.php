<?php

namespace App\Form;

use App\Entity\Playlist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Formulaire de création et modification d'une playlist.
 */
class PlaylistType extends AbstractType
{
    /**
     * Construit le formulaire de la playlist.
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('seuilReussite', IntegerType::class, [
                'label' => 'Seuil de réussite (/100)',
                'required' => false,
                'constraints' => [
                    new Range(['min' => 0, 'max' => 100,
                        'notInRangeMessage' => 'Le seuil doit être entre {{ min }} et {{ max }}.']),
                ],
            ])
        ;
    }

    /**
     * Configure les options du formulaire.
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Playlist::class,
        ]);
    }
}
