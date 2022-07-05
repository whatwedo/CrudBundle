<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\View;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use whatwedo\CoreBundle\Action\Action;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Block\DefinitionBlock;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\Form\Type\EntityAjaxType;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Form\Type\EntityPreselectType;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class DefinitionView
{
    protected ?object $data = null;

    protected PageInterface $route;

    protected ?FormInterface $form = null;

    protected DefinitionInterface $definition;

    protected \ReflectionObject $reflectionObject;

    public function __construct(
        protected DefinitionManager $definitionManager,
        protected FormRegistryInterface $formRegistry,
        protected FormFactoryInterface $formFactory,
        protected RouterInterface $router,
        protected RequestStack $requestStack,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected Reader $annotationReader,
        protected Security $security,
    ) {
    }

    public function create(DefinitionInterface $definition, PageInterface $route, ?object $data = null): self
    {
        $view = clone $this;
        $view->form = null;
        $view->setDefinition($definition);
        $view->setData($data);
        $view->setRoute($route);

        return $view;
    }

    public function setDefinition(DefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return object
     */
    public function getData()
    {
        return $this->data;
    }

    public function getRoute(): PageInterface
    {
        return $this->route;
    }

    public function setRoute(PageInterface $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getActions(): iterable
    {
        $actions = array_filter(
            $this->definition->getActions(),
            fn (Action $action) => in_array($this->route, $action->getOption('visibility'), true)
        );

        $filteredActions = [];

        $voterSubject = $this->getData();
        if (! $voterSubject) {
            $voterSubject = $this->getDefinition()::getEntity();
        }

        foreach ($actions as $action) {
            if ($action->getOption('voter_attribute') === null
            || $this->security->isGranted($action->getOption('voter_attribute'), $voterSubject)) {
                $filteredActions[] = $action;
            }
        }

        return $filteredActions;
    }

    /**
     * @return BlockCollection|Block[]
     */
    public function getBlocks(?PageInterface $page = null)
    {
        $blocks = $page
            ? $this->definition->getBuilder()->getBlocks()->filterVisibility($page)
            : $this->definition->getBuilder()->getBlocks();

        if ($page) {
            $attribute = match ($page) {
                Page::SHOW => 'show_voter_attribute',
                Page::CREATE => 'create_voter_attribute',
                Page::EDIT => 'edit_voter_attribute',
            };

            $blocks->filter(
                function (Block $block) use ($attribute) {
                    return $block->getOption($attribute) === null || $this->security->isGranted($block->getOption($attribute), $this->getData());
                }
            );
        }

        return $blocks;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPath(PageInterface $route, $params = [])
    {
        if ($this->definition->hasCapability($route)) {
            switch ($route) {
                case Page::SHOW:
                case Page::EDIT:
                case Page::DELETE:
                    if (! $this->data) {
                        return 'javascript:alert(\'can\\\'t generate route "' . $route->toRoute() . '" without data\')';
                    }

                    return $this->router->generate(
                        $this->definition::getRoute($route),
                        array_merge([
                            'id' => $this->data->getId(),
                        ], $params)
                    );
                case Page::AJAXFORM:
                    if (! $this->data) {
                        return $this->router->generate(
                            $this->definition::getRoute($route),
                            $params
                        );
                    }

                    return $this->router->generate(
                        $this->definition::getRoute($route),
                        array_merge([
                            'id' => $this->data->getId(),
                        ], $params)
                    );
                case Page::INDEX:
                case Page::BATCH:
                case Page::CREATE:
                    return $this->router->generate(
                        $this->definition::getRoute($route),
                        $params
                    );

                default:
                    return 'javascript:alert(\'can\\\'t generate route "' . $route . '".\')';
            }
        }

        return 'javascript:alert(\'Definition does not have the capability "' . $route . '".\')';
    }

    public function getEditForm(?FormBuilderInterface $builder = null): FormInterface
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        if ($builder === null) {
            $builder = $this->formFactory->createBuilder(
                FormType::class,
                $this->data,
                $this->definition->getFormOptions(Page::EDIT, $this->data)
            );
        }

        foreach ($this->getBlocks() as $block) {
            if (! $block->isVisibleOnEdit()
                || ! $this->authorizationChecker->isGranted($block->getEditVoterAttribute(), $this->data)) {
                continue;
            }

            if ($block instanceof DefinitionBlock && $block->getAccessorPath()) {
                $referencingData = $block->getData($this->data);
                $referencingDefinition = $block->getReferencingDefinition($referencingData);

                $referencingDefinition
                    ->createView($this->getRoute(), $referencingData)
                    ->getEditForm($builder)
                ;
                continue;
            }

            foreach ($block->getContents() as $content) {
                if (! $content->hasOption('form_type')
                    || ! $content->isVisibleOnEdit()
                    || ! $this->authorizationChecker->isGranted($content->getEditVoterAttribute(), $this->data)) {
                    continue;
                }

                $this->addFormChild($builder, $content);
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    public function getCreateForm(?FormBuilderInterface $builder = null): FormInterface
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        if ($builder === null) {
            $builder = $this->formFactory->createBuilder(
                FormType::class,
                $this->data,
                $this->definition->getFormOptions(Page::CREATE, $this->data)
            );
        }

        foreach ($this->getBlocks() as $block) {
            if (! $block->isVisibleOnCreate()
                || ! $this->authorizationChecker->isGranted($block->getCreateVoterAttribute(), $this->data)) {
                continue;
            }

            if ($block instanceof DefinitionBlock && $block->getAccessorPath()) {
                $referencingData = $block->getData($this->data);
                $referencingDefinition = $block->getReferencingDefinition($referencingData);

                $referencingDefinition
                    ->createView($this->getRoute(), $referencingData)
                    ->getCreateForm($builder)
                ;
                continue;
            }

            foreach ($block->getContents() as $content) {
                if (! $content->hasOption('form_type')
                    || ! $content->isVisibleOnCreate()
                    || ! $this->authorizationChecker->isGranted($content->getCreateVoterAttribute(), $this->data)) {
                    continue;
                }

                $this->addFormChild($builder, $content);
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    public function hasCapability(PageInterface $route): bool
    {
        return $this->definition->hasCapability($route);
    }

    public function getDefinition(): DefinitionInterface
    {
        return $this->definition;
    }

    protected function addFormChild(FormBuilderInterface $builder, AbstractContent $content): void
    {
        $formType = $this->getFormType($content);
        $formOptions = [
            'required' => $this->isContentRequired($content),
        ];
        if (! empty($this->definition->getFormAccessorPrefix())) {
            $formOptions['property_path'] = str_replace('_', '.', $this->definition->getFormAccessorPrefix() . $content->getAcronym());
        }

        $builder->add(
            $this->definition->getFormAccessorPrefix() . $content->getAcronym(),
            $formType,
            $content->getFormOptions($formOptions)
        );
    }

    protected function isContentRequired(AbstractContent $content): bool
    {
        return $this->formRegistry->getTypeGuesser()
            ->guessRequired($this->getDefinition()::getEntity(), $content->getOption('accessor_path'))
            ->getValue();
    }

    protected function getFormType(AbstractContent $content): ?string
    {
        $formType = $content->getOption('form_type');
        if ($formType === EntityPreselectType::class) {
            if (EntityPreselectType::isValueProvided($this->requestStack->getCurrentRequest(), $content->getFormOptions())) {
                $formType = EntityHiddenType::class;
            } else {
                $formType = EntityAjaxType::class;
            }
        }

        $content->setOption('form_type', $formType);

        return $formType;
    }

    /**
     * @return \ReflectionObject
     */
    protected function getReflectionObject()
    {
        if ($this->reflectionObject === null && $this->data) {
            $this->reflectionObject = new \ReflectionObject($this->data);
        }

        return $this->reflectionObject;
    }
}
