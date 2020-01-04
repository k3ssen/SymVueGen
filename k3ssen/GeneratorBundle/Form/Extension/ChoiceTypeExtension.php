<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
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
        $attr = $view->vars['attr'] ?? [];

        if ($options['tags']) {
            $attr['tags'] = $attr['tags'] ?? true;
        }

        $view->vars['attr'] = $attr;
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
        $formName = $form->getName();
        $parentForm = $form->getParent();
        if (!$parentForm) {
            throw new \RuntimeException(sprintf('Cannot use tags for form or field "%s": a parent form is required', $formName));
        }

        $options = $form->getConfig()->getOptions();
        if (in_array($choiceValue, $options['choices'])) {
            return;
        }

        $options['choices'] = array_merge($options['choices'], [$choiceValue => $choiceValue]);
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
