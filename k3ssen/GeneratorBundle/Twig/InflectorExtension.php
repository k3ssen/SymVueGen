<?php
declare(strict_types = 1);

namespace K3ssen\GeneratorBundle\Twig;

use Doctrine\Common\Inflector\Inflector;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class InflectorExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('tableize', [$this, 'tableize']),
            new TwigFilter('pluralize', [$this, 'pluralize']),
            new TwigFilter('singularize', [$this, 'singularize']),
            new TwigFilter('camelize', [$this, 'camelize']),
            new TwigFilter('classify', [$this, 'classify']),
        ];
    }

    public function tableize($string)
    {
        return Inflector::tableize($string);
    }

    public function pluralize($string)
    {
        return Inflector::pluralize($string);
    }

    public function singularize($string)
    {
        return Inflector::singularize($string);
    }

    public function camelize($string)
    {
        return Inflector::camelize($string);
    }

    public function classify($string)
    {
        return Inflector::classify($string);
    }
}
