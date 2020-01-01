<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\MetaEntity;
use App\Entity\MetaProperty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MetaEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $metaEntity = $builder->getData();
        $builder
            ->add('name', null, [
                'row_attr' => [
                    'cols' => 12,
                ],
            ])
            ->add('traits', ChoiceType::class, [
                'multiple' => true,
                'required' => false,
                'choices' => [
                    'TimestampableTrait' => 'TimestampableTrait',
                    'BlameableTrait' => 'BlameableTrait',
                ],
                'row_attr' => [
                    'cols' => 6,
                ],
            ])
            ->add('interfaces', ChoiceType::class, [
                'multiple' => true,
                'required' => false,
                'choices' => [
                    'TimestampableInterface' => 'TimestampableInterface',
                    'BlameableInterface' => 'BlameableInterface',
                ],
                'row_attr' => [
                    'cols' => 6,
                ],
            ])
            ->add('metaProperties', CollectionType::class, [
                'label' => 'Properties',
                'entry_type' => MetaPropertyType::class,
                'allow_add' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MetaEntity::class,
        ]);
    }
}
