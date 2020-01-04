<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Entity;

use BadMethodCallException;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Validator\Constraints as Assert;
use Twig\Error\RuntimeError;

/**
 * @ORM\Entity(repositoryClass="K3ssen\GeneratorBundle\Repository\MetaPropertyRepository")
 */
class MetaProperty
{
    const MANY_TO_ONE = 'many_to_one';
    const ONE_TO_MANY = 'one_to_many';
    const MANY_TO_MANY = 'many_to_many';
    const ONE_TO_ONE = 'one_to_one';

    public const TYPES = [
        Types::ARRAY => 'array',
        Types::BIGINT => 'int',
        Types::BINARY => 'string',
        Types::BLOB => 'string',
        Types::BOOLEAN => 'bool',
        Types::DATE_MUTABLE => '\DateTime',
        Types::DATE_IMMUTABLE => '\DateTimeImmutable',
        Types::DATEINTERVAL => '\DateInterval',
        Types::DATETIME_MUTABLE => '\DateTime',
        Types::DATETIME_IMMUTABLE => '\DateTimeImmutable',
        Types::DATETIMETZ_MUTABLE => '\DateTime',
        Types::DATETIMETZ_IMMUTABLE => '\DateTimeImmutable',
        Types::DECIMAL => 'string',
        Types::FLOAT => 'float',
        Types::GUID => 'string',
        Types::INTEGER => 'int',
        Types::JSON => 'array',
        Types::OBJECT => '\stdClass', // fixme: what to use? it's very possible that stdClass isn't correct.
        Types::SIMPLE_ARRAY => 'array',
        Types::SMALLINT => 'int',
        Types::STRING => 'string',
        Types::TEXT => 'string',
        Types::TIME_MUTABLE => '\DateTime',
        Types::TIME_IMMUTABLE => '\DateTimeImmutable',

        self::MANY_TO_ONE => null,
        self::ONE_TO_MANY => 'Collection',
        self::MANY_TO_MANY => 'Collection',
        self::ONE_TO_ONE => null,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="MetaEntity", inversedBy="metaProperties", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private MetaEntity $metaEntity;

    /**
     * @ORM\Column(type="string")
     */
    private string $name = '';

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $nullable = false;

    /**
     * @ORM\Column(type="boolean", name="`unique`")
     */
    private bool $unique = false;

    /**
     * Length could be used for precision as well.
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\LessThan(255)
     */
    private ?int $length = null;

    /**
     * Should only apply to decimal type.
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $scale = null;

    /**
     * @ORM\Column(type="string")
     */
    private string $type = Types::STRING;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $targetEntity = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $mappedBy = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $inversedBy = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $orphanRemoval = false;

    /**
     * @ORM\Column(type="string", name="`default`")
     */
    private ?string $default = null;

    public function __construct(MetaEntity $metaEntity)
    {
        $this->metaEntity = $metaEntity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMetaEntity(): ?MetaEntity
    {
        return $this->metaEntity;
    }

    public function setMetaEntity($metaEntity): self
    {
        $this->metaEntity = $metaEntity;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = Inflector::camelize($name);
        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): self
    {
        $this->unique = $unique;
        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function setScale(?int $scale): self
    {
        if ($this->type !== Types::DECIMAL) {
            throw new BadMethodCallException(sprintf('Cannot set scale for type %s.', $this->getType()));
        }
        $this->scale = $scale;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getReturnType(): string
    {
        $returnType = static::TYPES[$this->getType()];
        if (!$returnType) {
            $returnType = $this->getTargetEntityName();
        } if ($this->isNullable() || !$this->getDefault()) {
            return '?' . $returnType;
    }
        return $returnType;
    }

    public function isRelationship(): bool
    {
        return in_array($this->getType(), [static::MANY_TO_ONE, static::ONE_TO_MANY, static::MANY_TO_MANY, static::ONE_TO_ONE], true);
    }

    public function setType(string $type): self
    {
        if (!array_key_exists($type, static::TYPES)) {
            throw new InvalidArgumentException(sprintf('Type %s is not a valid type', $type));
        }
        $this->type = $type;
        switch ($type) {
            case Types::DECIMAL:
                $this->scale = 2;
                break;
            case static::ONE_TO_MANY:
            case static::MANY_TO_MANY:
            $this->getMetaEntity()
                ->addUse('use Doctrine\Common\Collections\ArrayCollection;')
                ->addUse('use Doctrine\Common\Collections\Collection;')
            ;
                break;
        }
        return $this;
    }

    public function getTargetEntity(): ?string
    {
        if (!$this->targetEntity || !$this->isRelationship()) {
            return null;
        }
        if (strpos('\\', $this->targetEntity)) {
            $this->targetEntity = $this->getMetaEntity()->getNamespace() . '\\' . $this->targetEntity;
        }
        return $this->targetEntity;
    }

    public function setTargetEntity(?string $targetEntity): self
    {
//        if (!$this->isRelationship()) {
//            throw new BadMethodCallException('TargetEntity can only be set on relationship types.');
//        }
//        $this->getMetaEntity()->addUse(sprintf('use %s;', $targetEntity));
        $this->targetEntity = $targetEntity;
        return $this;
    }

    public function getTargetEntityName(): ?string
    {
        try {
            if (strpos($this->getTargetEntity(), '\\')) {
                $parts = explode('\\', $this->getTargetEntity());
                $targetEntityName = array_pop($parts);
                return $targetEntityName;
            }
            return $this->getTargetEntity();
        } catch (\Throwable $exception) {
            return $this->getTargetEntity();
        }
    }

    public function getMappedBy(): ?string
    {
        return $this->mappedBy;
    }

    public function setMappedBy(?string $mappedBy)
    {
        if (!in_array($this->getType(), [static::ONE_TO_MANY, static::MANY_TO_MANY, static::ONE_TO_ONE], true)) {
            throw new BadMethodCallException(sprintf('MappedBy cannot be set on %s type.', $this->getType()));
        }
        $this->mappedBy = $mappedBy;
    }

    public function getInversedBy(): ?string
    {
        return $this->inversedBy;
    }

    public function setInversedBy(?string $inversedBy): self
    {
        if (!in_array($this->getType(), [static::MANY_TO_ONE, static::MANY_TO_MANY, static::ONE_TO_ONE], true)) {
            throw new BadMethodCallException(sprintf('InversedBy cannot be set on %s type.', $this->getType()));
        }
        $this->inversedBy = $inversedBy;
        return $this;
    }

    public function isOrphanRemoval(): bool
    {
        return $this->orphanRemoval;
    }

    public function setOrphanRemoval(bool $orphanRemoval): self
    {
        if (!in_array($this->getType(), [static::MANY_TO_ONE, static::MANY_TO_MANY, static::ONE_TO_MANY], true)) {
            throw new BadMethodCallException(sprintf('OrphanRemoval cannot be set on %s type.', $this->getType()));
        }
        $this->orphanRemoval = $orphanRemoval;
        return $this;
    }

    public function getDefault(): ?string
    {
        $default = $this->default;
        if (!$default) {
            if($this->isNullable()) {
                $default = 'null';
            } elseif (in_array($this->getType(), [Types::STRING, Types::TEXT])) {
                $default = '""';
            } elseif (in_array($this->getType(), [Types::INTEGER, Types::SMALLINT, Types::BIGINT])) {
                $default = '0';
            } elseif (in_array($this->getType(), [Types::BOOLEAN])) {
                $default = 'false';
            } elseif (in_array($this->getType(), [Types::ARRAY, Types::SIMPLE_ARRAY])) {
                $default = '[]';
            } elseif (in_array($this->getType(), [Types::DECIMAL])) {
                $default = '"0.00"';
            }
        }
        return $default;
    }

    public function setDefault(?string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function getAnnotations(): array
    {
        $annotations = [];
        if ($this->isRelationship()) {
            $annotations[] = $this->getOrmRelationShipAnnotation();
            if (!$this->getInversedBy()) {
                $annotations[] = $this->getOrmJoinColumnAnnotation();
            }
        } else {
            $annotations[] = $this->getOrmColumnAnnotation();
        }
        return $annotations;
    }

    protected function getOrmColumnAnnotation(): string
    {
        $type = $this->getType();
        $annotationProperties = [sprintf('type="%s"', $type)];
        if ($this->isNullable()){
            $annotationProperties[] = 'nullable=true';
        }
        if ($length = $this->getLength()) {
            $annotationProperties[] = ($type === Types::DECIMAL ? 'precision=' : 'length=') . $length;
        }
        if ($scale = $this->getScale()) {
            $annotationProperties[] = 'scale=' . $scale;
        }
        if ($this->isUnique()){
            $annotationProperties[] = 'unique=true';
        }

        return sprintf('@ORM\Column(%s)', implode(', ', $annotationProperties));
    }

    protected function getOrmRelationShipAnnotation(): string
    {
        $annotationProperties = [sprintf('targetEntity="%s"', $this->getTargetEntity())];
        if ($mappedBy = $this->getMappedBy()) {
            $annotationProperties[] = sprintf('mappedBy="%s"', $this->getMappedBy());
        } elseif ($mappedBy = $this->getInversedBy()) {
            $annotationProperties[] = sprintf('inversedBy="%s"', $this->getInversedBy());
        }
        if ($this->isOrphanRemoval()) {
            $annotationProperties[] = 'orphanRemoval=true';
        }
        if ($this->getMappedBy()) {
            $annotationProperties[] = 'cascade={"persist"}';
        }
        return sprintf('@ORM\%s(%s)', Inflector::classify($this->getType()), implode(', ', $annotationProperties));
    }

    protected function getOrmJoinColumnAnnotation(): string
    {
        $annotationProperties = [];
        // In relationships, nullable is true by default
        if (!$this->isNullable()){
            $annotationProperties[] = 'nullable=false';
        }
        $annotationProperties[] = 'onDelete="RESTRICT"';
        return sprintf('@ORM\JoinColumn(%s)', implode(', ', $annotationProperties));
    }
}
