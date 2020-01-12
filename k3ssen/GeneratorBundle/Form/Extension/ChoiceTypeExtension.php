<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Form\Extension;

use K3ssen\GeneratorBundle\Vue\VueDataStorage;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ChoiceTypeExtension extends AbstractTypeExtension
{
    /**
     * @var VueDataStorage
     */
    private $vueDataStorage;

    public function __construct(VueDataStorage $vueDataStorage)
    {
        $this->vueDataStorage = $vueDataStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('tags', false);
        $resolver->setAllowedTypes('tags', ['boolean', 'callable']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        if ($options['tags']) {
            $this->addTagsFieldListener($builder, is_callable($options['tags']) ? $options['tags'] : null);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->setChoicesVModel($view);
        $attr = $view->vars['attr'] ?? [];

        if ($options['tags']) {
            $attr['tags'] = $attr['tags'] ?? true;
        }

        $view->vars['attr'] = $attr;
    }

    protected function setChoicesVModel(FormView $view)
    {
        $choices = [];
        $this->setChoiceVModelData($view->vars['choices'], $choices);
        $vChoicesModelName = $this->getVueChoicesModelName($view);

        $this->vueDataStorage->addData($vChoicesModelName, $choices);

        $view->vars['attr'][':items'] = $view->vars['attr'][':items'] ?? $vChoicesModelName;
    }

    protected function setChoiceVModelData(array $choiceSet, array &$choices)
    {
        foreach ($choiceSet as $choiceOrGroup) {
            if ($choiceOrGroup instanceof ChoiceGroupView) {
                $choices[] = ['header' => $choiceOrGroup->label];
                $this->setChoiceVModelData($choiceOrGroup->choices, $choices);
            } elseif ($choiceOrGroup instanceof ChoiceView) {
                $choices[] = [
                    'text' => $choiceOrGroup->label,
                    'value' => $choiceOrGroup->value,
                    'attr' => $choiceOrGroup->attr,
                ];
            }
        }
    }

    protected function getVueChoicesModelName(FormView $view): string
    {
        $name = $view->parent ? $this->getVueChoicesModelName($view->parent) : 'form_choices';
        // Numeric would imply we're dealing with a Collection. In that case just return the (parent) name without
        // appending anything. This way all collection-items will share the same choices-model.
        if (is_numeric($view->vars['name'])) {
            // If you need choices per-item, then use the commented line below.
            //return $name . '['.$view->vars['name'].']';
            return $name;
        } else {
            if ($name) {
                $name .= '.';
            }
            $name .= $view->vars['name'];
        }
        return $name;
    }

    protected function addTagsFieldListener(FormBuilderInterface $builder, ?callable $createCallback = null)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->addChoice($event->getForm(), $event->getData());
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $this->addChoice($event->getForm(), $event->getData());
        });
    }

    protected function addChoice(FormInterface $form, $choiceValue)
    {
        if (!$choiceValue) {
            return;
        }
        $formName = $form->getName();
        $parentForm = $form->getParent();
        if (!$parentForm) {
            throw new \RuntimeException(sprintf('Cannot use tags for form or field "%s": a parent form is required', $formName));
        }

        $options = $form->getConfig()->getOptions();
        if (in_array($choiceValue, $options['choices'])) {
            return;
        }
        $options['choices'][$choiceValue] = $choiceValue;
        $options['data'] = $choiceValue;

        $parentForm->add($formName, ChoiceType::class, $options);

        $accessor = new PropertyAccessor();
        $parentData = $form->getParent()->getData();
        $accessor->setValue($parentData, $form->getName(), $choiceValue);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }
}
