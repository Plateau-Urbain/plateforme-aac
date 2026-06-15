<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use App\Entity\User;
use App\Entity\Application;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('civility', ChoiceType::class, [
                'choices' => array_flip(User::getAllCivilities()),
                'expanded' => true,
                'label' => "Civilité",
                'attr' => ['class' => 'pu-radios']
            ])
            ->add('firstname', TextType::class, ['label' => "Prénom", 'attr' => ['class' => 'form-control']])
            ->add('lastname', TextType::class, ['label' => "Nom", 'attr' => ['class' => 'form-control']])
            ->add('email', EmailType::class, [
                'label' => "Email", 
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true
                ],
                'disabled' => true
            ])
            ->add('phone', TelType::class, [
                'label' => "Téléphone", 
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 01 23 45 67 89 ou +33 1 23 45 67 89',
                    'required' => 'required'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le téléphone est obligatoire.',
                        'groups' => ['projectHolder', 'Default']
                    ]),
                    new Regex([
                        'pattern' => '/^(\+33\s?[1-9](\s?\d{2}){4}|0[1-9](\s?\d{2}){4})$/',
                        'message' => 'Le format du téléphone n\'est pas valide. Utilisez le format français (01 23 45 67 89) ou international (+33 1 23 45 67 89).',
                        'groups' => ['projectHolder', 'Default']
                    ])
                ]
            ])
            ->add('birthday', BirthdayType::class, [
                'label'  => 'Date de naissance',
                'input'  => 'datetime',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control']
            ])
            ->add('oldPassword', PasswordType::class, [
                    'mapped' => false, 'required' => false,
                    'label' => "Mot de passe actuel",
                    'attr' => ['class' => 'form-control']
                ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                'invalid_message' => 'Les deux champs doivent être identique',
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Répéter le mot de passe'],
                'attr' => ['class' => 'form-control']
            ])        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'inherit_data' => true,
        ]);
    }
}
