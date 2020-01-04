<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Generator;

use K3ssen\GeneratorBundle\Entity\MetaEntity;

trait GeneratorFileLocatorTrait
{
    protected function getTargetFile(MetaEntity $metaEntity): string
    {
        return $this->projectDir
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Entity'
            . DIRECTORY_SEPARATOR . $metaEntity->getName() . '.php';
    }
}