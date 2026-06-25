<?php

namespace App\Form;

use App\Entity\Inscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class InscriptionNoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('note', IntegerType::class, [
            'label' => 'Note (/100)',
            'constraints' => [
                new NotBlank(['message' => 'La note est obligatoire.']),
                new Range(['min' => 0, 'max' => 100,
                    'notInRangeMessage' => 'La note doit être entre {{ min }} et {{ max }}.']),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Inscription::class]);
    }
}
