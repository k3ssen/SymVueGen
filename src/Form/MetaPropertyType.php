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
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                ],
                'attr' => [
                    'label' => 'Property name',
                ]
            ])
            ->add('type', ChoiceType::class, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                ],
                'choices' => array_combine($keys = array_keys(MetaProperty::TYPES), $keys),
            ])
            ->add('length', null, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => '!isRelationType($$.type)',
                ],
                'attr' => [
                    ':label' => '$$.type === "decimal" ? "precision" : "length"',
                    'hint' => 'Leave blank for default',
                ]
            ])
            ->add('scale', null, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => '$$.type === "decimal"',
                ],
            ])
            ->add('targetEntity', null, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => 'isRelationType($$.type)',
                ],
                'attr' => [
                    ':required' => 'isRelationType($$.type)',
                ]
            ])
            ->add('mappedBy', null, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => '["many_to_many", "one_to_many", "one_to_one"].includes($$.type)',
                ],
                'attr' => [
                    ':disabled' => '$$.inversedBy > ""',
                    ':messages' => '$$.inversedBy > "" ? "InversedBy already set" : ""',
                ],
            ])
            ->add('inversedBy', null, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => '["many_to_many", "many_to_one", "one_to_one"].includes($$.type)',
                ],
                'attr' => [
                    'hint' => 'Leave blank if not inversed',
                    ':disabled' => '$$.mappedBy > ""',
                    ':messages' => '$$.mappedBy > "" ? "MappedBy already set" : ""',
                ],
            ])
            ->add('nullable', null, [
                'label' => 'N',
                'row_attr' => [
                    'title' => 'Nullable',
                    'class' => 'col-4 col-sm-2 col-md-1',
                    'v-if' => '!["many_to_many"].includes($$.type)',
                ],
                'attr' => [
                    ':messages' => '$$.type === "one_to_many" ? "On mappedBy" : ""',
                ]
            ])
            ->add('unique', null, [
                'label' => 'U',
                'row_attr' => [
                    'title' => 'Unique',
                    'class' => 'col-4 col-sm-2 col-md-1',
                    'v-if' => '!["many_to_many"].includes($$.type)',
                ],
                'attr' => [
                    ':messages' => '$$.type === "one_to_many" ? "On mappedBy" : ""',
                ]
            ])
            ->add('orphanRemoval', null, [
                'label' => 'OR',
                'row_attr' => [
                    'title' => 'Orphan removal',
                    'class' => 'col-4 col-sm-2 col-md-1',
                    'v-if' => 'isRelationType($$.type) && $$.type !== "one_to_one"',
                ],
                'attr' => [
                    ':messages' => '$$.type === "many_to_one" ? "Will be set on the opposing side" : ""',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MetaProperty::class,
        ]);
    }
}
