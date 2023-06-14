<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class PhotoEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // Champ photo
            ->add('photo', FileType::class, [
                'label' => 'Sélectionner une nouvelle photo',
                'attr' => [
                    'accept' => 'image/jpeg, image/png',
                ],
                'constraints' => [

                    new NotBlank([
                        'message' => 'Sélectionnez une nouvelle photo',
                    ]),

                    new File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Fichier trop volumineux. La taille maximum autorisée est de {{ limit }} {{ suffix }}',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'L\'image doit être de type jpg ou png !',
                    ]),
                ],
            ])

            // Bouton de validation
            ->add('save', SubmitType::class, [
                'label' => 'Changer la photo',
                'attr' => [
                    'class' => 'btn btn-outline-primary w-100',
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

        ]);
    }
}
