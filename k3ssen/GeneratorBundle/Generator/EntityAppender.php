<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Generator;

use K3ssen\GeneratorBundle\Entity\MetaEntity;
use Twig\Environment;

class EntityAppender
{
    use GeneratorFileLocatorTrait;

    protected Environment $twig;

    protected string $projectDir;

    public function __construct(Environment $twig, string $projectDir) {
        $this->twig = $twig;
        $this->projectDir = $projectDir;
    }

    public function appendFields(MetaEntity $pseudoMetaEntity): string
    {
        $diffMetaEntity = $this->getMetaEntityDiff($pseudoMetaEntity);
        $targetFile = $this->getTargetFile($diffMetaEntity);
        $currentContent = file_get_contents($targetFile);

        $this->addUsages($diffMetaEntity, $currentContent);
        $this->addConstructorContent($diffMetaEntity, $currentContent);
        $this->addProperties($diffMetaEntity, $currentContent);
        $this->getAddedMethods($diffMetaEntity, $currentContent);

        file_put_contents($targetFile, $currentContent);
        return $targetFile;
    }

    protected function getMetaEntityDiff(MetaEntity $pseudoMetaEntity): MetaEntity
    {
        $currentMetaEntity = EntityReader::readEntity($pseudoMetaEntity->getClass());
        $diffMetaEntity = clone $pseudoMetaEntity;
        foreach ($currentMetaEntity->getMetaProperties() as $property) {
            foreach ($diffMetaEntity->getMetaProperties() as $diffProperty) {
                if ($diffProperty->getName() === $property->getName()) {
                    $diffMetaEntity->removeMetaProperty($diffProperty);
                }
            }
        }
        return $diffMetaEntity;
    }

    protected function addUsages(MetaEntity $diffMetaEntity, string &$currentContent)
    {
        //First we check and remove usages that are already defined.
        $usages = [];
        foreach ($diffMetaEntity->getUsages() as $usage) {
            if (strpos($currentContent, $usage) === false) {
                $usages[] = $usage;
            }
        }
        $usageContent = $this->twig->render('@Generator/skeleton/entity/_usages.php.twig', [
            'meta_entity' => $diffMetaEntity,
            'usages' => $usages,
        ]);

        $this->insertStrAfterLastMatch($currentContent, $usageContent, '/use (\w+\\\\.+);/');
    }

    protected function addConstructorContent(MetaEntity $diffMetaEntity, string &$currentContent)
    {
        $hasConstructor = strpos($currentContent, 'public function __construct(') !== false;
        $propertyContent = $this->twig->render('@Generator/skeleton/entity/_construct.php.twig', [
            'meta_entity' => $diffMetaEntity,
            'inner_content_only' => $hasConstructor,
        ]);
        if ($hasConstructor) {
            $this->insertStrAfterLastMatch($currentContent, $propertyContent, '/public function __construct\(.*\)\n    /');
        } else {
            $this->insertStrAfterLastMatch($currentContent, $propertyContent, '/(protected|private|public) \$\w+;/');
        }
    }

    protected function addProperties(MetaEntity $diffMetaEntity, string &$currentContent)
    {
        $propertyContent = $this->twig->render('@Generator/skeleton/entity/properties.php.twig', [
            'meta_entity' => $diffMetaEntity,
            'skip_id' => true,
        ]);
        $this->insertStrAfterLastMatch($currentContent, $propertyContent, '/(protected|private|public) ?\??\w* \$\w+;/');
    }

    protected function getAddedMethods(MetaEntity $diffMetaEntity, string &$currentContent)
    {
        $methodsContent = $this->twig->render('@Generator/skeleton/entity/property_methods.php.twig', [
            'meta_entity' => $diffMetaEntity,
            'skip_id' => true,
        ]);

        preg_match_all('/\}/', $currentContent, $matches, PREG_OFFSET_CAPTURE);
        $lastMatch = array_pop($matches[0]);
        $position = $lastMatch[1];
        $currentContent = substr_replace($currentContent, $methodsContent, $position, 0);
    }

    protected function insertStrAfterLastMatch(string &$baseString, string $insertString, string $pattern)
    {
        preg_match_all($pattern, $baseString, $matches, PREG_OFFSET_CAPTURE);
        $lastMatch = array_pop($matches[0]);
        if (is_array($lastMatch) && count($lastMatch) > 1) {
            $position = $lastMatch[1] + strlen($lastMatch[0]) + 1;
            $baseString = substr_replace($baseString, $insertString, $position, 0);
        }
    }
}
