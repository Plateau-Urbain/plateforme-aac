<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'plainPassword',
            RepeatedType::class,
            [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirmation',
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir un mot de passe.'),
                    new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
            ]
        );
    }
}
