<?php
declare(strict_types=1);

namespace App\Form\Extension;

use App\Vue\VueDataStorage;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($view->vars['compound'] ?? true) {
            return;
        }
        $this->setVModel($view);

        $this->replaceVModelShortTag($view);
    }

    /**
     * Replaces '$$.' with 'form.parent_model_name.' to make it easier to refer to other fields inside form attributes.
     * This is especially useful for collections.
     * Example:
     * ['v-if' => '$$.numberOfPages']
     * in a BookType form that is a collection within LibraryType would become something like
     * ['v-if' => 'form.library.books[0].numberOfPages']
     * for the first item and
     * ['v-if' => 'form.library.books[__name__].numberOfPages']
     * for the prototype.
     */
    protected function replaceVModelShortTag(FormView $view)
    {
        $vueParentModelName = $view->parent ? $this->getVueModelName($view->parent) : 'form';
        foreach ($view->vars['attr'] as $key => $attrVal) {
            if (is_string($attrVal)) {
                $view->vars['attr'][$key] = str_replace('$$.', $vueParentModelName.'.', $attrVal);
            }
        }
        foreach ($view->vars['row_attr'] as $key => $attrVal) {
            if (is_string($attrVal)) {
                $view->vars['row_attr'][$key] = str_replace('$$.', $vueParentModelName.'.', $attrVal);
            }
        }
    }

    protected function setVModel(FormView $view)
    {
        $attr = $view->vars['attr'] ?? [];
        $attr['v-model'] = $this->getVueModelName($view);
        $value = $view->vars['value'];
        if (array_key_exists('checked', $view->vars)) {
            $value = $view->vars['checked'] ? "1" : "0";
        }
        $this->vueDataStorage->addData($attr['v-model'], $value);
        $view->vars['attr'] = $attr;
    }

    protected function getVueModelName(FormView $view): string
    {
        $name = $view->parent ? $this->getVueModelName($view->parent) : 'form';
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
