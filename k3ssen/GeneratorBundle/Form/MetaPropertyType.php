<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Form;

use K3ssen\GeneratorBundle\Entity\MetaProperty;
use Symfony\Component\Filesystem\Filesystem;
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
        $types = array_combine($keys = array_keys(MetaProperty::TYPES), $keys);
        $types[MetaProperty::MANY_TO_ONE] = 'm2o (ManyToOne / mto)';
        $types[MetaProperty::ONE_TO_MANY] = 'o2m (OneToMany / otm)';
        $types[MetaProperty::MANY_TO_MANY] = 'm2m (ManyToMany / mtm)';
        $types[MetaProperty::ONE_TO_ONE] = 'o2o (OneToOne / oto)';

        $entityDir = $this->projectDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity';
        $finder = new Finder();
        /** @var \SplFileInfo $file */
        $entityNames = [];
        foreach ($finder->in($entityDir)->contains('@ORM\Entity') as $file ) {
            $entityNames[] = str_replace('.php', '', $file->getFilename());
        }

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
                'choices' => array_combine($types, array_keys(MetaProperty::TYPES)),
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
            ->add('targetEntity', ChoiceType::class, [
                'choices' => array_combine($entityNames, $entityNames),
                'tags' => true,
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

//        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
//            $data = $event->getData();
//            $this->addTargetEntityChoice($event->getForm(), $data->getTargetEntity());
//        });
//
//        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
//            $data = $event->getData();
//            $this->addTargetEntityChoice($event->getForm(), $data['targetEntity']);
//        });
    }
//
//    protected function addTargetEntityChoice(FormInterface $form, $targetEntity)
//    {
//        $config = $form->get('targetEntity')->getConfig();
//        $options = $config->getOptions();
//        $options['choices'] = array_merge($options['choices'], [$targetEntity => $targetEntity]);
//        $form->add('targetEntity', ChoiceType::class, $options);
//    }


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
