<?php

namespace App\Form;

use App\Entity\SpaceImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

use Symfony\Component\Validator\Constraints\File;

class SpaceImageType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', VichImageType::class, [
            'label' => false,
            'required' => false,
            'download_uri' => false,
            'image_uri' => true,
            'attr' => [
                'accept' => 'image/jpeg,image/jpg,image/png,image/webp',
            ],
            'constraints' => [
                new File([
                    'maxSize' => '600k',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/jpg',
                        'image/png',
                        'image/webp',
                    ],
                    'mimeTypesMessage' => 'Seuls les formats JPEG, PNG et WebP sont acceptés pour les photos (max 600 Ko).',
                    'maxSizeMessage' => 'La photo est trop volumineuse ({{ size }} {{ suffix }}). La taille maximale autorisée est 600 Ko.',
                    'groups' => ['Default', 'save', 'standard', 'multi_location'],
                ]),
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            if ($event->getData() === null) {
                $event->setData(new SpaceImage());
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event): void {
            $data = $event->getData();
            if ($data instanceof SpaceImage) {
                if (!$data->getFileType()) {
                    $data->setFileType(SpaceImage::FILETYPE_IMAGE);
                }
                if ($data->getUpdatedAt() === null) {
                    $data->setUpdatedAt(new \DateTime());
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SpaceImage::class,
            'empty_data' => static fn (): SpaceImage => new SpaceImage(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_spaceimage';
    }
}
