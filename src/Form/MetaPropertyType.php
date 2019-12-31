<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\MetaProperty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MetaPropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
            ])
            ->add('type', ChoiceType::class, [
                'choices' => array_combine($keys = array_keys(MetaProperty::TYPES), $keys),
            ])
            ->add('length', null, [
                'row_attr' => [
                    'cols' => 2,
                    'v-if' => '!["many_to_one", "many_to_many", "one_to_many", "one_to_one"].includes($$.type)',
                ]
            ])
            ->add('targetEntity', null, [
                'row_attr' => [
                    'cols' => 2,
                    'v-if' => '["many_to_one", "many_to_many", "one_to_many", "one_to_one"].includes($$.type)',
                ]
            ])
            ->add('nullable', null, [
            ])
            ->add('unique', null, [
            ])
//            ->add('unique')
//            ->add('length')
//            ->add('scale')
//            ->add('targetEntity')
//            ->add('mappedBy')
//            ->add('inversedBy')
//            ->add('orphanRemoval')
//            ->add('metaEntity')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MetaProperty::class,
        ]);
    }
}
