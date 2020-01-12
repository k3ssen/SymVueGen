<?php
declare(strict_types=1);

namespace App\Entity\Traits;

trait TestTrait
{
    /**
     * @ORM\Column(type="string", unique=false)
     */
    protected string $testproperty = "5";

    public function getTestproperty(): ?string
    {
        return $this->testproperty;
    }

    public function setTestproperty(string $testproperty)
    {
        $this->testproperty = $testproperty;
    }
}