<?php
declare(strict_types=1);

namespace App\Form\Extension;

use App\Vue\VueDataStorage;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypeExtension extends AbstractTypeExtension
{
    /**
     * @var VueDataStorage
     */
    private $vueDataStorage;

    public function __construct(VueDataStorage $vueDataStorage)
    {
        $this->vueDataStorage = $vueDataStorage;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
//        $resolver->setDefined(['cols']);
        $resolver->setDefaults([
            'v-model' => null,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($view->vars['compound'] ?? true) {
            return;
        }
        $attr = $view->vars['attr'] ?? [];
        if ($options['v-model'] ?? false) {
            if ($attr['v-model'] ?? false) {
                throw new \RuntimeException('option "v-model" should not be defined if it is also defined inside the "attr" option');
            }
            $attr['v-model'] = $options['v-model'];
        }
        $attr['v-model'] = 'form.'.$this->getVueModelName($view); //$attr['v-model'] ?? 'form.'.$view->vars['id'];

        $rowAttr = $view->vars['row_attr'] ?? [];
//        $rowAttr['cols'] = $options['cols'] ?? $rowAttr['cols'] ?? 12;

        $value = $view->vars['value'];
        if (array_key_exists('checked', $view->vars)) {
            $value = $view->vars['checked'] ? "1" : "0";
        }

        if ($view->parent) {
            $vueParentModelName = $this->getVueModelName($view->parent);
            foreach ($attr as $key => $attrVal) {
                if (is_string($attrVal)) {
                    $attr[$key] = str_replace('$$.', 'form.'.$vueParentModelName.'.', $attrVal);
                }
            }
            foreach ($rowAttr as $key => $attrVal) {
                if (is_string($attrVal)) {
                    $rowAttr[$key] = str_replace('$$.', 'form.'.$vueParentModelName.'.', $attrVal);
                }
            }
        }

        $this->vueDataStorage->addData($attr['v-model'], $value);
        $view->vars['attr'] = $attr;
        $view->vars['row_attr'] = $rowAttr;
    }

    protected function getVueModelName(FormView $view): string
    {
        $name = '';
        if ($view->parent) {
            $name = $this->getVueModelName($view->parent);
        }
        if (is_numeric($view->vars['name'])) {
            return $name . '['.$view->vars['name'].']';
        } else {
            if ($name) {
                $name .= '.';
            }
            $name .= $view->vars['name'];
        }
        return $name;
    }
}
