<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Generator;

use K3ssen\GeneratorBundle\Entity\MetaEntity;
use K3ssen\GeneratorBundle\Entity\MetaProperty;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class EntityGenerator
{
    use GeneratorFileLocatorTrait;

    private EntityAppender $entityAppender;

    protected Environment $twig;

    protected string $projectDir;

    public function __construct(
        EntityAppender $entityAppender,
        Environment $twig,
        string $projectDir
    ) {
        $this->entityAppender = $entityAppender;
        $this->twig = $twig;
        $this->projectDir = $projectDir;
    }

    public function createEntity(MetaEntity $metaEntity): array
    {
        $entityFileData = $this->getEntityContent($metaEntity);

        $targetFile = $this->getTargetFile($metaEntity);

        $fs = new Filesystem();
        $fs->dumpFile($targetFile, $entityFileData);
        $affectedFiles[] = $targetFile;
        $affectedFiles[] = $this->createRepository($metaEntity);

        return array_merge($affectedFiles, $this->generateMissingInversedOrMappedBy($metaEntity));
    }
//
//    public function updateEntity(MetaEntity $pseudoMetaEntity): array
//    {
//        return array_merge(
//            [$this->entityAppender->appendFields($pseudoMetaEntity)],
//            $this->generateMissingInversedOrMappedBy($pseudoMetaEntity)
//        );
//    }

    protected function generateMissingInversedOrMappedBy(MetaEntity $metaEntity): array
    {
        $affectedFiles = [];
        foreach ($metaEntity->getMetaProperties() as $property) {
            if ($targetEntity = $property->getTargetEntity()) {
                $existingClass = class_exists($targetEntity);
                $targetMetaEntity = $existingClass ? EntityReader::readEntity($targetEntity) : new MetaEntity($targetEntity);
                if ($this->checkHasInversedProperty($targetMetaEntity, $property)) {
                    continue;
                }
                $this->setInversedProperty($property, $targetMetaEntity);

                if ($existingClass) {
                    $affectedFiles[] = $this->entityAppender->appendFields($targetMetaEntity);
                } else {
                    $affectedFiles = array_merge($affectedFiles, $this->createEntity($targetMetaEntity));
                }
            }
        }
        return $affectedFiles;
    }

    protected function checkHasInversedProperty(MetaEntity $targetMetaEntity, MetaProperty $property): bool
    {
        foreach ($targetMetaEntity->getMetaProperties() as $targetProperty) {
            if ($targetProperty->getName() === $property->getInversedBy() || $targetProperty->getName() === $property->getMappedBy()) {
                return true;
            }
        }
        return false;
    }

    protected function setInversedProperty(MetaProperty $property, MetaEntity $targetMetaEntity)
    {
        $inversedProperty = new MetaProperty($targetMetaEntity);
        $targetMetaEntity->addMetaProperty($inversedProperty);
        $inversedProperty->setTargetEntity($property->getMetaEntity()->getClass());

        switch ($property->getType()) {
            case MetaProperty::MANY_TO_ONE:
                $inversedProperty->setType(MetaProperty::ONE_TO_MANY);
                break;
            case MetaProperty::ONE_TO_MANY:
                $inversedProperty->setType(MetaProperty::MANY_TO_ONE);
                break;
            case MetaProperty::MANY_TO_MANY:
                $inversedProperty->setType(MetaProperty::MANY_TO_MANY);
                break;
            case MetaProperty::ONE_TO_ONE:
                $inversedProperty->setType(MetaProperty::ONE_TO_ONE);
                break;
        }
        if ($mappedBy = $property->getMappedBy()) {
            $inversedProperty
                ->setName($mappedBy)
                ->setInversedBy($property->getName())
                ->setOrphanRemoval($property->isOrphanRemoval())
                ->setNullable($property->isNullable())
                ->setUnique($property->isUnique())
            ;
        } elseif ($inversedBy = $property->getInversedBy()) {
            $inversedProperty
                ->setName($inversedBy)
                ->setMappedBy($property->getName())
            ;
        }
    }

    public function createRepository(MetaEntity $metaEntity): string
    {
        $repoFileData = $this->getRepositoryContent($metaEntity);
        $targetFile = str_replace([DIRECTORY_SEPARATOR.'Entity', '.php'], [DIRECTORY_SEPARATOR.'Repository', 'Repository.php'], $this->getTargetFile($metaEntity));

        $fs = new Filesystem();
        $fs->dumpFile($targetFile, $repoFileData);

        return $targetFile;
    }

    protected function getRepositoryContent(MetaEntity $metaEntity)
    {
        return $this->twig->render('@Generator/skeleton/repository/Repository.php.twig', [
            'meta_entity' => $metaEntity,
        ]);
    }

    protected function getEntityContent(MetaEntity $metaEntity)
    {
        return $this->twig->render('@Generator/skeleton/entity/Entity.php.twig', [
            'meta_entity' => $metaEntity,
        ]);
    }
}