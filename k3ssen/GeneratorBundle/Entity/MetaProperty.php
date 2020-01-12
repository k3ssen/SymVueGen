<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Entity;

use BadMethodCallException;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
     * @Assert\NotBlank()
     */
    private ?string $name = '';

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
    private ?int $scale = null; // Only for decimal type

    /**
     * Should only apply to decimal type.
     * @ORM\Column(type="boolean", nullable=true, name="`unsigned`")
     */
    private ?bool $unsigned = null; // Only for numeric types (tinyint, smallint, mediumint, int, bigint)

    /**
     * @ORM\Column(type="string")
     */
    private ?string $type = Types::STRING;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $targetEntity = null; // Only for relationship types

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $mappedBy = null; // Only for relationship types

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $inversedBy = null; // Only for relationship types

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $orphanRemoval = false; // Only for relationship types

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $assertValid = true; // Only for relationship types

    /**
     * @ORM\Column(type="string", name="`default`", nullable=true)
     */
    private ?string $default = null;

    /**
     * @ORM\Column(type="boolean", name="`indexed`", nullable=true)
     */
    private ?bool $indexed = null;

    public function __construct(MetaEntity $metaEntity)
    {
        $this->metaEntity = $metaEntity;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        $type = $this->getType();
        // check if the name is actually a fake name
        if (!$this->isRelationship() && !array_key_exists($type, static::TYPES)) {
            $context->buildViolation(sprintf('Type "%s" is not a valid type', $type))
                ->atPath('type')
                ->addViolation();
        }
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

    public function setName(?string $name): self
    {
        $this->name = $name ? Inflector::camelize($name) : null;
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

    public function isUnsigned(): ?bool
    {
        return $this->unsigned;
    }

    public function setUnsigned(?bool $unsigned): self
    {
        $this->unsigned = $unsigned;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getReturnType(): string
    {
        $returnType = static::TYPES[$this->getType()];
        if (!$returnType) {
            $returnType = $this->getTargetEntityName();
        }
        // If null is not allowed, then Assert/NotNull should be used instead of throwing exceptions.
        if ($returnType !== 'Collection') {
            $returnType = '?' . $returnType;
        }
        return $returnType;
    }

    public function isRelationship(): bool
    {
        return in_array($this->getType(), [static::MANY_TO_ONE, static::ONE_TO_MANY, static::MANY_TO_MANY, static::ONE_TO_ONE], true);
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTargetEntity(): ?string
    {
        if (!$this->targetEntity || !$this->isRelationship()) {
            return null;
        }
        if (strpos($this->targetEntity, '\\') === false) {
            $this->targetEntity = $this->getMetaEntity()->getNamespace() . '\\' . $this->targetEntity;
        }
        return $this->targetEntity;
    }

    public function setTargetEntity(?string $targetEntity): self
    {
        $this->targetEntity = $targetEntity;
        return $this;
    }

    public function getTargetEntityName(): ?string
    {
        try {
            if (strpos($this->getTargetEntity(), '\\') !== false) {
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
        if ($mappedBy && !in_array($this->getType(), [static::ONE_TO_MANY, static::MANY_TO_MANY, static::ONE_TO_ONE], true)) {
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

    public function isAssertValid(): bool
    {
        return $this->assertValid;
    }

    public function setAssertValid(bool $assertValid): self
    {
        $this->assertValid = $assertValid;
        return $this;
    }

    public function getDefault(): ?string
    {
        $default = $this->default;
        if (!$default && $this->getReturnType() !== 'Collection') {
            $default = 'null';
        }
        return $default;
    }

    public function setDefault(?string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function isIndexed(): ?bool
    {
        return !$this->isUnique() && !$this->isRelationship() && $this->indexed;
    }

    public function setIndexed(?bool $indexed): self
    {
        $this->indexed = $indexed;
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
        $annotations = array_merge($annotations, $this->getAssertAnnotations());
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
        if ($this->isUnsigned()){
            $annotationProperties[] = 'options={"unsigned":true}';
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
        if ($this->isOrphanRemoval() && in_array($this->getType(), [static::MANY_TO_MANY, static::ONE_TO_MANY])) {
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

    public function getAssertAnnotations(): array
    {
        $asserts = [];
        if (!$this->isNullable()) {
            if ($this->getReturnType() === 'string') {
                $asserts[] = '@Assert\NotBlank';
            } elseif ($this->getReturnType() !== 'Collection') {
                $asserts[] = '@Assert\NotNull';
            }
        }
        if ($this->isRelationship() && $this->isAssertValid()) {
            $asserts[] = '@Assert\Valid';
        }

        if ($this->getType() === Types::SMALLINT) {
            $asserts[] = '@Assert\Range(min=−32768, max=32767)';
        } elseif ($this->getType() === Types::SMALLINT && $this->isUnsigned()) {
            $asserts[] = '@Assert\Range(min=0, max=65535 )';
        } elseif ($this->isUnsigned()){
            $asserts[] = '@Assert\PositiveOrZero';
        }
        if ($length = $this->getLength()) {
            $asserts[] = '@Assert\Length(max=' . $length . ')';
        }

        if ($this->getType() === Types::GUID) {
            $asserts[] = '@Assert\Uuid';
        }
        if ($this->getType() === Types::STRING) {
            if (stripos($this->getName(), 'email') !== false) {
                $asserts[] = '@Assert\Email';
            }
        }
        return $asserts;
    }
}
