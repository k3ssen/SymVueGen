<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="K3ssen\GeneratorBundle\Repository\MetaEntityRepository")
 */
class MetaEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private ?string $name = '';

    /**
     * @ORM\Column(type="string")
     */
    private string $namespace = 'App\Entity';

    /**
     * @var string[]
     * @ORM\Column(type="json_array", nullable=true)
     */
    public ?array $interfaces = null;

    /**
     * @var string[]
     * @ORM\Column(type="json_array", nullable=true)
     */
    public ?array $traits = null;

    /**
     * @ORM\OneToMany(targetEntity="K3ssen\GeneratorBundle\Entity\MetaProperty", mappedBy="metaEntity", orphanRemoval=true, cascade={"persist"})
     * @Assert\Valid
     */
    private $metaProperties;

    public function __construct(?string $name = null)
    {
        $this->metaProperties = new ArrayCollection();

        if ($name && strpos($name, '\\') !== false) {
            $parts = explode('\\Entity\\', $name);
            $name = array_pop($parts);
        }
        if ($name) {
            $this->setName($name);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = Inflector::classify($name);
        return $this;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = Inflector::classify($namespace);
        return $this;
    }

    public function getClass(): string
    {
        return $this->getNamespace() . '\\' . $this->getName();
    }

    public function getRepositoryClass(): string
    {
        return str_replace('\Entity\\', '\Repository\\', $this->getClass()) . 'Repository';
    }

    public function getRepositoryNamespace(): string
    {
        return str_replace('\Entity', '\Repository', $this->getNamespace());
    }

    public function getUsages(): array
    {
        $usages = [
            'Doctrine\ORM\Mapping as ORM',
        ];
        foreach ($this->getMetaProperties() as $metaProperty) {
            $targetEntity = $metaProperty->getTargetEntity();
            if ($targetEntity && !$this->hasSameNamespace($targetEntity)) {
                $usages[] = $targetEntity;
            }
            if ($metaProperty->getType() === MetaProperty::MANY_TO_MANY || $metaProperty->getType() === MetaProperty::ONE_TO_MANY) {
                $usages[] = 'Doctrine\Common\Collections\ArrayCollection';
                $usages[] = 'Doctrine\Common\Collections\Collection';
            }
            if ($metaProperty->getAssertAnnotations()) {
                $usages[] = 'Symfony\Component\Validator\Constraints as Assert';
            }
        }
        $traits = $this->getTraits() ?: [];
        foreach ($traits as $namespace) {
            if ($namespace && !$this->hasSameNamespace($namespace)) {
                $usages[] = $namespace;
            }
        }
        $interfaces = $this->getInterfaces() ?: [];
        foreach ($interfaces as $namespace) {
            if ($namespace && !$this->hasSameNamespace($namespace)) {
                $usages[] = $namespace;
            }
        }
        return array_unique($usages);
    }

    protected function hasSameNamespace(string $class): bool
    {
        $parts = explode('\\', $class);
        array_pop($parts);
        return $this->getNamespace() === implode('\\', $parts);
    }

    public function getAnnotations(): array
    {
        $annotations = [
            sprintf('@ORM\Entity(repositoryClass="%s")', $this->getRepositoryClass()),
        ];
        return array_merge($annotations, $this->getTableAnnotations());
    }

    public function getTableAnnotations(): array
    {
        $annotations = [];
        /** @var MetaProperty[]|ArrayCollection $indexedProperties */
        $indexedProperties = $this->getMetaProperties()->filter(function(MetaProperty $metaProperty) {
            return $metaProperty->isIndexed();
        });
        if ($indexedProperties->count() > 0) {
            $annotations[] = '@ORM\Table(indexes={';
            $tableAnnotations = [];
            foreach ($indexedProperties as $property) {
                $tableAnnotations[] = sprintf(
                    '    @ORM\Index(name="idx_%s_%s", columns={"%s"})',
                    Inflector::tableize($this->getName()),
                    Inflector::tableize($property->getName()),
                    $property->getName()
                );
            }
            $annotations[] = implode(',', $tableAnnotations);
            $annotations[] = '}';
        }
        return $annotations;
    }

    public function getInterfaces(): array
    {
        return array_unique($this->interfaces ?: []);
    }

    public function getInterfaceNames(): array
    {
        $names = [];
        foreach ($this->getInterfaces() ?: [] as $class) {
            $parts = explode('\\', $class);
            $names[] = array_pop($parts);
        }
        return $names;
    }

    public function addInterface(string $interface): self
    {
        $this->interfaces[] = $interface;
        return $this;
    }

    public function getTraits(): array
    {
        return array_unique($this->traits ?: []);
    }

    public function getTraitNames(): array
    {
        $names = [];
        foreach ($this->getTraits() ?: [] as $class) {
            $parts = explode('\\', $class);
            $names[] = array_pop($parts);
        }
        return $names;
    }

    public function addTrait(string $trait): self
    {
        $this->traits[] = $trait;
        return $this;
    }

    /**
     * @return Collection|MetaProperty[]
     */
    public function getMetaProperties(): Collection
    {
        return $this->metaProperties;
    }

    public function addMetaProperty(MetaProperty $metaProperty): self
    {
        if (!$this->metaProperties->contains($metaProperty)) {
            $this->metaProperties[] = $metaProperty;
            $metaProperty->setMetaEntity($this);
        }

        return $this;
    }

    public function removeMetaProperty(MetaProperty $metaProperty): self
    {
        if ($this->metaProperties->contains($metaProperty)) {
            $this->metaProperties->removeElement($metaProperty);
        }

        return $this;
    }

    public function __toString()
    {
        return (string) $this->getName();
    }
}
