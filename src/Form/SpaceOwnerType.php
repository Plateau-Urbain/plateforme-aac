<?php
// vim:expandtab:sw=4 softtabstop=4:
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;
use App\Form\CompanyType;
use App\Form\UserType;

class SpaceOwnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userInfo', UserType::class, ['mapped' => false])
            ->add('companyInfo', CompanyType::class, [
                'mapped' => false,
                'legacy_company_status' => $builder->getData() instanceof \App\Entity\User
                    ? $builder->getData()->getCompanyStatus()
                    : null,
            ])
        ;


        $builder->remove('username');
        $userInfoForm = $builder->get('userInfo');
        $userInfoForm->remove('phone')
                     ->remove('birthday')
                     ->remove('description');
        $companyForm = $builder->get('companyInfo');
        $companyForm->remove('companyDescription')
                    ->remove('companyEffective')
                    ->remove('companyStructures')
                    ->remove('companyBlog');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\User::class,
            'validation_groups' => ['owner', 'Default']
        ]);
    }

    // The FormTypeInterface::getName()
    // method is deprecated since Symfony 2.8 and will be removed in 3.0.
    // Remove it from your classes. Use getBlockPrefix() if you want
    // to customize the template block prefix.
    // This method will be added to the FormTypeInterface with Symfony 3.0
    //public function getName()
    public function getBlockPrefix()
    {
        return 'project_owner';
    }
}
