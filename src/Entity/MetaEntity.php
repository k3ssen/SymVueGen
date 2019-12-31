<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MetaEntityRepository")
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
     */
    private string $name = 'SomeEntityName';

    /**
     * @ORM\Column(type="string")
     */
    private string $namespace = 'App\Entity';

    /**
     * @var string[]
     * @ORM\Column(type="simple_array")
     */
    private array $uses = [
        'use Doctrine\ORM\Mapping as ORM;'
    ];

    /**
     * @var string[]
     * @ORM\Column(type="simple_array", nullable=true)
     */
    public ?array $interfaces = null;

    /**
     * @var string[]
     * @ORM\Column(type="simple_array", nullable=true)
     */
    public ?array $traits = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\MetaProperty", mappedBy="metaEntity", orphanRemoval=true, cascade={"persist"})
     * @Assert\Valid()
     */
    private $metaProperties;

    public function __construct()
    {
        $this->metaProperties = new ArrayCollection();
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

    public function getClass(): string
    {
        return $this->namespace . '\\' . $this->name;
    }

    public function getRepositoryClass(): string
    {
        return str_replace('\Entity\\', '\Repository\\', $this->getClass()) . 'Repository';
    }

    public function getUses(bool $evaluate = false): array
    {
        return $this->uses;
    }

    public function addUse(string $use): self
    {
        if (!in_array($use, $this->uses, true)) {
            $this->uses[] = $use;
        }
        return $this;
    }

    public function getAnnotations(): array
    {
        return [
            sprintf('@ORM\Entity(repositoryClass="%s")', $this->getRepositoryClass()),
        ];
    }

    public function getInterfaces(bool $evaluate = false): ?array
    {
        return $this->interfaces;
    }

    public function addInterface(string $interface): self
    {
        $this->interfaces[] = $interface;
        return $this;
    }

    public function getTraits(bool $evaluate = false): ?array
    {
        return $this->traits;
    }

    public function addTrait(string $traits): self
    {
        $this->traits[] = $traits;
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
            // set the owning side to null (unless already changed)
            if ($metaProperty->getMetaEntity() === $this) {
                $metaProperty->setMetaEntity(null);
            }
        }

        return $this;
    }
}
