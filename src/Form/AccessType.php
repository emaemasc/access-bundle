<?php

namespace Ema\AccessBundle\Form;

use Ema\AccessBundle\Contracts\AccessGroupConfig;
use Ema\AccessBundle\Contracts\AccessPresetConfig;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\EmaAccessBundle;
use Ema\AccessBundle\Form\DataTransformer\AccessRoleDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccessType extends AbstractType
{
    private array $items;
    private array $groups;
    private array $presets;
    public function __construct(
        private readonly AccessRoleStore    $roleStore,
        private readonly AccessGroupConfig  $groupConfig,
        private readonly AccessPresetConfig $presetConfig,
    ) {
        $this->items = $this->roleStore->getRoles();
        $this->groups = $this->groupConfig->all();
        $this->presets = $this->presetConfig->all();

        foreach ($this->items as $item) {
            if ($item->getGroup()) {
                $this->groups[$item->getGroup()]->items[] = $item->getName();
            }
            foreach ($item->getPresets() as $preset) {
                if (isset($this->presets[$preset])) {
                    $this->presets[$preset]->items[] = $item->getName();
                }
            }
        }

        foreach ($this->groups as $key => $group) {
            if (empty($group->items)) {
                unset($this->groups[$key]);
            }
        }

        foreach ($this->presets as $key => $preset) {
            if (empty($preset->items)) {
                unset($this->presets[$key]);
            }
        }

        usort($this->groups, function ($a, $b) {
            return $a->sort <=> $b->sort;
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->items as $item) {
            $builder->add('item_' . $item->getName(), CheckboxType::class, [
                'label' => $item->getTitle(),
                'required' => false,
                'attr' => [
                    'class' => 'emaemasc-input',
                    'data-item-id' => $item->getName(),
                    'data-item-name' => $item->getName(),
                    'data-item-title' => $item->getTitle()
                ],
                'label_attr' => [
                    'class' => 'emaemasc-input-label'
                ],
                'property_path' => '[' . $item->getName() . ']'
            ]);
        }

        $builder->addModelTransformer(new AccessRoleDataTransformer($this->roleStore));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['items'] = $this->items;
        $view->vars['groups'] = $this->groups;
        $view->vars['presets'] = $this->presets;
        $view->vars['toggle_attributes'] = $options['toggle_attributes'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'toggle_attributes' => [],
            'compound' => true,
        ]);

        $resolver->setAllowedTypes('toggle_attributes', 'array');
    }

    public function getBlockPrefix(): string
    {
        return EmaAccessBundle::NAME;
    }
}
