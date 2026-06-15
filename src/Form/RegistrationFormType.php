<?php

namespace App\Form;

use App\Entity\User;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse email'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez renseigner votre adresse email.'),
                    new Email(message: 'Adresse email invalide.'),
                ],
            ])
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'first_options' => [
                        'label' => 'Mot de passe',
                        'attr' => ['class' => 'form-control', 'placeholder' => 'Mot de passe'],
                    ],
                    'second_options' => [
                        'label' => 'Confirmation',
                        'attr' => ['class' => 'form-control', 'placeholder' => 'Confirmation'],
                    ],
                    'invalid_message' => 'Les mots de passe doivent correspondre.',
                    'constraints' => [new NotBlank(message: 'Veuillez choisir un mot de passe.')],
                ]
            )
            ->add('captcha', CaptchaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'project_holder_user_registration';
    }
}
