<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Generator;

use K3ssen\GeneratorBundle\Entity\MetaEntity;
use K3ssen\GeneratorBundle\Entity\MetaProperty;
use ReflectionClass;
use ReflectionProperty;

class EntityReader
{
    protected $traitPropertyNames = [];

    public static function readEntity(string $nameOrClass): MetaEntity
    {
        return (new self())->createMetaEntity($nameOrClass);
    }

    public function createMetaEntity(string $nameOrClass): MetaEntity
    {
        $this->traitPropertyNames = [];
        $metaEntity = new MetaEntity($nameOrClass);
        $reflectionClass = new ReflectionClass($metaEntity->getClass());
        $this->setTraits($metaEntity, $reflectionClass);
        $this->setInterfaces($metaEntity, $reflectionClass);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $this->setMetaProperty($metaEntity, $reflectionProperty, $reflectionClass);
        }
        return $metaEntity;
    }

    protected function setInterfaces(MetaEntity $metaEntity, ReflectionClass $reflectionClass)
    {
        $interfaces = $reflectionClass->getInterfaces();
        foreach ($interfaces as $interface) {
            $metaEntity->addInterface($interface->getName());
        }
    }

    protected function setTraits(MetaEntity $metaEntity, ReflectionClass $reflectionClass)
    {
        $traits = $reflectionClass->getTraits();
        foreach ($traits as $trait) {
            $metaEntity->addTrait($trait->getName());
            foreach ($trait->getProperties() as $property) {
                $this->traitPropertyNames[] = $property->getName();
            }
        }
    }

    protected function setMetaProperty(MetaEntity $metaEntity, ReflectionProperty $reflectionProperty, ReflectionClass $reflectionClass)
    {
        $name = $reflectionProperty->getName();
        $docComment = $reflectionProperty->getDocComment();
        if (!$docComment || in_array($name, $this->traitPropertyNames) || $name === 'id') {
            return null;
        }
        $metaProperty = new MetaProperty($metaEntity);
        $metaProperty->setName($name);
        $this->setType($metaProperty, $docComment, $reflectionClass);
        $this->setNullable($metaProperty, $docComment);
        $this->setUnique($metaProperty, $docComment);

        $metaEntity->addMetaProperty($metaProperty);
    }

    protected function setType(MetaProperty $metaProperty, string $docComment, ReflectionClass $reflectionClass)
    {
        if ($match = $this->findMatch($docComment, 'type')) {
            $metaProperty->setType($match);
            $this->setUnsigned($metaProperty, $docComment);
            $this->setIndexed($metaProperty, $reflectionClass);
            return;
        } elseif (strpos($docComment, 'ManyToOne') !== false) {
            $metaProperty->setType(MetaProperty::MANY_TO_ONE);
        } elseif (strpos($docComment, 'OneToMany') !== false) {
            $metaProperty->setType(MetaProperty::ONE_TO_MANY);
        } elseif (strpos($docComment, 'ManyToMany') !== false) {
            $metaProperty->setType(MetaProperty::MANY_TO_MANY);
        } elseif (strpos($docComment, 'OneToOne') !== false) {
            $metaProperty->setType(MetaProperty::ONE_TO_ONE);
        }
        $this->setTargetEntity($metaProperty, $docComment);
    }

    protected function setLength(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, 'length')) {
            $metaProperty->setLength((int) $match);
        } elseif ($match = $this->findMatch($docComment, 'precision')) {
            $metaProperty->setLength((int) $match);
        }
    }

    protected function setScale(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, 'scale')) {
            $metaProperty->setScale((int) $match);
        }
    }

    protected function setTargetEntity(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, 'targetEntity')) {
            $metaProperty->setTargetEntity($match);
            $this->setMappedBy($metaProperty, $docComment);
            $this->setInversedBy($metaProperty, $docComment);
            $this->setOrphanRemoval($metaProperty, $docComment);
            $this->setAssertValid($metaProperty, $docComment);
        }
    }

    protected function setMappedBy(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, 'mappedBy')) {
            $metaProperty->setMappedBy($match);
        }
    }

    protected function setInversedBy(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, 'inversedBy')) {
            $metaProperty->setInversedBy($match);
        }
    }

    protected function setOrphanRemoval(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, 'orphanRemoval')) {
            $metaProperty->setOrphanRemoval($match === 'true' || $match === '1');
        }
    }

    protected function setNullable(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, 'nullable')) {
            $metaProperty->setNullable($match === 'true' || $match === '1');
        }
    }

    protected function setUnique(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, 'unique')) {
            $metaProperty->setUnique($match === 'true' || $match === '1');
        }
    }

    protected function setUnsigned(MetaProperty $metaProperty, string $docComment)
    {
        if ($match = $this->findMatch($docComment, '"?unsigned"?')) {
            $metaProperty->setUnsigned(true);
        }
    }

    protected function setAssertValid(MetaProperty $metaProperty, string $docComment)
    {
        $metaProperty->setAssertValid((bool) preg_match('/@Assert\\\\Valid/', $docComment, $matches));
    }

    protected function setIndexed(MetaProperty $metaProperty, ReflectionClass $reflectionClass)
    {
        $docComment = $reflectionClass->getDocComment();
        $indexed = stripos($docComment, sprintf('columns={"%s"}', $metaProperty->getName())) !== false;
        $metaProperty->setIndexed($indexed);
    }

    protected function findMatch(string $docComment, string $searchProperty): ?string
    {
        $pattern = '/'.$searchProperty.' ?= ?'.'"?([\w\\\\]+)"?/';
        preg_match($pattern, $docComment, $matches);
        return $matches[1] ?? null;
    }
}
