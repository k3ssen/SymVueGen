<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Form;

use K3ssen\GeneratorBundle\Entity\MetaEntity;
use K3ssen\GeneratorBundle\Entity\MetaProperty;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MetaEntityType extends AbstractType
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

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
                'choices' => $this->getTraitChoices(),
                'row_attr' => [
                    'cols' => 6,
                ],
            ])
            ->add('interfaces', ChoiceType::class, [
                'multiple' => true,
                'required' => false,
                'choices' => $this->getInterfaceChoices(),
                'row_attr' => [
                    'cols' => 6,
                ],
            ])
            ->add('metaProperties', CollectionType::class, [
                'label' => 'Properties<p style="font-weight: normal">Note that the "id" field will always be generated. If you want to use a different identifier, you\'ll need to manually change this after generation.</p>',
                'entry_type' => MetaPropertyType::class,
                'prototype_data' => new MetaProperty($metaEntity),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;
    }

    protected function getTraitChoices(): array
    {
        $finder = new Finder();
        $traits = [];
        foreach ($finder->in($this->getEntityDir())->contains('/trait [A-Z]/i') as $file ) {
            /** @var \SplFileInfo $file */
            $contents = file_get_contents($file->getRealPath(), true, null, 0, 100);
            preg_match('/namespace ([\w\\\]+);/i', $contents, $matches);
            $namespace = $matches[1] ?? null;
            if (!$namespace) {
                continue;
            }
            $traitName = str_replace('.php', '', $file->getFilename());
            $traits[$traitName] = $namespace. '\\'. $traitName;
        }
        return $traits;
    }

    protected function getInterfaceChoices(): array
    {
        $finder = new Finder();
        $interfaces = [];
        foreach ($finder->in($this->getEntityDir())->contains('/interface [A-Z]/i') as $file ) {
            /** @var \SplFileInfo $file */
            $contents = file_get_contents($file->getRealPath(), true, null, 0, 100);
            preg_match('/namespace ([\w\\\]+);/i', $contents, $matches);
            $namespace = $matches[1] ?? null;
            if (!$namespace) {
                continue;
            }
            $interfaceName = str_replace('.php', '', $file->getFilename());
            $interfaces[$interfaceName] = $namespace. '\\'. $interfaceName;
        }
        return $interfaces;
    }

    protected function getEntityDir(): string
    {
        return $this->projectDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MetaEntity::class,
        ]);
    }
}
