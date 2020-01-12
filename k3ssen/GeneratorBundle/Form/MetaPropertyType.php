<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Form;

use K3ssen\GeneratorBundle\Entity\MetaProperty;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MetaPropertyType extends AbstractType
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

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
                'attr' => [
                    ':items' => 'types',
                    ':filter' => 'filterTypes',
                    ':messages' => 'typeMessage($$.type, $$.unsigned)'
                ],
                'choices' => $this->getTypeChoices(),
            ])
            ->add('length', null, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => 'hasLength($$.type)',
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
            ->add('unsigned', null, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => 'hasUnsigned($$.type)',
                ],
                'attr' => [
                    'messages' => 'Only positives',
                ],
            ])
            ->add('targetEntity', ChoiceType::class, [
                'choices' => $this->getEntityChoices(),
                'tags' => true,
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => 'isRelationType($$.type)',
                ],
                'attr' => [
                    ':required' => 'isRelationType($$.type)',
                ],
            ])
            ->add('mappedBy', null, [
                'row_attr' => [
                    'class' => 'col-6 col-sm-3 col-md-2',
                    'v-if' => '["many_to_many", "one_to_many", "one_to_one"].includes($$.type)',
                ],
                'attr' => [
                    ':disabled' => '$$.inversedBy > ""',
                    ':messages' => '$$.inversedBy > "" ? "InversedBy already set" : ""',
                    ':required' => '$$.type === "one_to_many"'
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
                    'class' => 'col-4 col-sm-2 col-md-1',
                    'v-if' => '!["many_to_many"].includes($$.type)',
                ],
                'attr' => [
                    'messages' => 'nullable',
                ]
            ])
            ->add('unique', null, [
                'label' => 'U',
                'row_attr' => [
                    'class' => 'col-4 col-sm-2 col-md-1',
                    'v-if' => 'canBeUnique($$.type)',
                ],
                'attr' => [
                    'messages' => 'unique',
                ]
            ])
            ->add('orphanRemoval', null, [
                'label' => 'OR',
                'row_attr' => [
                    'class' => 'col-4 col-sm-2 col-md-1',
                    'v-if' => '["one_to_many", "many_to_one"].includes($$.type)',
                ],
                'attr' => [
                    'messages' => 'Orphan removal',
                ]
            ])
            ->add('assertValid', null, [
                'label' => 'AV',
                'row_attr' => [
                    'class' => 'col-4 col-sm-2 col-md-1',
                    'v-if' => 'isRelationType($$.type)',
                ],
                'attr' => [
                    'messages' => '@Assert\Valid',
                ],
            ])
            ->add('indexed', null, [
                'label' => 'Idx',
                'row_attr' => [
                    'class' => 'col-4 col-sm-2 col-md-1',
                    'v-if' => '(!$$.unique || $$.unique == "0") && canBeUnique($$.type)',
                    'title' => 'Add index if you expect this field to be searched on (eg used in where-condititons)',
                ],
                'attr' => [
                    'messages' => 'Add index',
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var MetaProperty $data */
            $data = $event->getData();
            if ($data->getTargetEntity() && strpos($data->getTargetEntity(), 'App\\Entity') !== false) {
                $form = $event->getForm();
                $options = $form->get('targetEntity')->getConfig()->getOptions();
                $options['data'] = $data->getTargetEntityName();
                $form->add('targetEntity', ChoiceType::class, $options);
            }
        });
    }

    public function getTypeChoices(): array
    {
        $type = array_keys(MetaProperty::TYPES);
        return array_combine($type, $type);
    }

    protected function getEntityChoices(): array
    {
        $finder = new Finder();
        /** @var \SplFileInfo $file */
        $entityNames = [];
        foreach ($finder->in($this->getEntityDir())->contains('@ORM\Entity') as $file ) {
            $entityNames[] = str_replace('.php', '', $file->getFilename());
        }

        return array_combine($entityNames, $entityNames);
    }

    protected function getEntityDir(): string
    {
        return $this->projectDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity';
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MetaProperty::class,
            'empty_data' => function (FormInterface $form) {
                $metaEntity = $form->getParent()->getParent()->getData();
                return new MetaProperty($metaEntity);
            },
        ]);
    }
}
