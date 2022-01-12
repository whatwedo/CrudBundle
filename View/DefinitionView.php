<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\View;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Column;
use ReflectionObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Twig\Environment;
use whatwedo\CrudBundle\Action\Action;
use whatwedo\CrudBundle\Action\IdentityableActionInterface;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Content\Content;
use whatwedo\CrudBundle\Content\RelationContent;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\VisibilityEnum;
use whatwedo\CrudBundle\Form\Type\EntityAjaxType;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Form\Type\EntityPreselectType;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class DefinitionView
{
    protected ?object $data = null;
    protected Page $route;
    protected ?FormInterface $form = null;
    protected DefinitionInterface $definition;
    protected ReflectionObject $reflectionObject;

    public function __construct(
        protected DefinitionManager $definitionManager,
        protected Environment $templating,
        protected FormRegistryInterface $formRegistry,
        protected FormFactoryInterface $formFactory,
        protected RouterInterface $router,
        protected RequestStack $requestStack,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected Reader $annotationReader,
    ) { }

    public function create(DefinitionInterface $definition, Page $route, ?object $data = null): self
    {
        $view = clone $this;
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

    public function getRoute(): Page
    {
        return $this->route;
    }

    public function setRoute(Page $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getActions(): iterable
    {
        return array_filter(
            $this->definition->getActions(),
            fn(Action $action) => in_array($this->route, $action->getOption('visibility'), true)
        );
    }

    /**
     * @param int|null $visibility
     * @return BlockCollection|Block[]
     */
    public function getBlocks(?Page $page = null)
    {
        return $page
            ? $this->definition->getBuilder()->getBlocks()->filterVisibility($page)
            : $this->definition->getBuilder()->getBlocks();
    }

    public function render(): string
    {
        return $this->templating->render($this->getTemplatePath('_boxes/'.$this->getRoute().'.html.twig'), [
            'data' => $this->data,
            'helper' => $this,
        ]);
    }

    /**
     * @param string $value text to be rendered
     *
     * @return string html
     */
    public function linkIt($value, Content $content)
    {
        return (string)$value;
        // TODO refactor
        $entity = $content->getContents($this->data);
        $def = $this->definitionManager->getDefinitionByEntity($entity);

        if (null !== $def) {
            if ($this->authorizationChecker->isGranted(Page::SHOW, $entity)
                && $def::hasCapability(Page::SHOW)) {
                $path = $this->router->generate($def::getRouteName(Page::SHOW), [
                    'id' => $entity->getId(),
                ]);

                $granted = false;
                if (! $this->authorizationChecker->isGranted('IS_AUTHENTICATED_ANONYMOUSLY')) {
                    // if the user is not authenticated, we link it because a
                    // login form is shown if the user tries to access the resource
                    // otherwise there would happens a InsufficientAuthenticationException
                    $fakeRequest = Request::create($path, 'GET');
                    [$roles, $channel] = (new AccessMap())->getPatterns($fakeRequest);
                    foreach ($roles as $role) {
                        $granted = $granted || $this->authorizationChecker->isGranted($role);
                    }
                } else {
                    $granted = true;
                }

                if ($granted) {
                    return sprintf('<a href="%s">%s</a>', $path, $value);
                }
            }
        }

        return $value;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPath(Page $route, $params = [])
    {
        if ($this->definition->hasCapability($route)) {
            switch ($route) {
                case Page::SHOW:
                case Page::EDIT:
                case Page::DELETE:
                    if (! $this->data) {
                        return 'javascript:alert(\'can\\\'t generate route "'.$route->toRoute().'" without data\')';
                    }

                    return $this->router->generate(
                        $this->definition::getRouteName($route),
                        array_merge([
                            'id' => $this->data->getId(),
                        ], $params)
                    );
                case Page::AJAX:
                    if (! $this->data) {
                        return $this->router->generate(
                            $this->definition::getRouteName($route),
                            $params
                        );
                    }

                    return $this->router->generate(
                        $this->definition::getRouteName($route),
                        array_merge([
                            'id' => $this->data->getId(),
                        ], $params)
                    );
                case Page::INDEX:
                case Page::BATCH:
                case Page::CREATE:
                    return $this->router->generate(
                        $this->definition::getRouteName($route),
                        $params
                    );

                default:
                    return 'javascript:alert(\'can\\\'t generate route "'.$route.'".\')';
            }
        }

        return 'javascript:alert(\'Definition does not have the capability "'.$route.'".\')';
    }

    public function getEditForm(): FormInterface
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        $builder = $this->formFactory->createBuilder(FormType::class, $this->data, []);

        foreach ($this->getBlocks() as $block) {
            if (!$block->isVisibleOnEdit()
                || !$this->authorizationChecker->isGranted($block->getEditVoterAttribute(), $this->data)) {
                continue;
            }

            foreach ($block->getContents() as $content) {
                if (!$content->hasOption('form_type')
                    || !$content->isVisibleOnEdit()
                    || !$this->authorizationChecker->isGranted($content->getEditVoterAttribute(), $this->data)) {
                    continue;
                }

                $formType = $this->getFormType($content);

                $builder->add(
                    $content->getAcronym(),
                    $formType,
                    $content->getFormOptions([
                        'required' => $this->isContentRequired($content),
                    ])
                );
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    public function getCreateForm(): FormInterface
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        $builder = $this->formFactory->createBuilder(FormType::class, $this->data);

        foreach ($this->getBlocks() as $block) {
            if (!$block->isVisibleOnCreate()
                || !$this->authorizationChecker->isGranted($block->getCreateVoterAttribute(), $this->data)) {
                continue;
            }

            foreach ($block->getContents() as $content) {
                if (!$content->hasOption('form_type')
                    || !$content->isVisibleOnCreate()
                    || !$this->authorizationChecker->isGranted($content->getCreateVoterAttribute(), $this->data)) {
                    continue;
                }

                $formType = $this->getFormType($content);

                $builder->add(
                    $content->getAcronym(),
                    $formType,
                    $content->getFormOptions([
                        'required' => $this->isContentRequired($content),
                    ])
                );
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    /**
     * @param bool $onlylisten
     *
     * @return string
     */
    public function getAjaxListen($onlylisten = false)
    {
        $data = $this->definition->addAjaxOnChangeListener();
        if ($onlylisten) {
            $data = array_filter($data, function ($item) {
                return AbstractDefinition::AJAX_LISTEN === $item;
            });
        }
        $ret = '[';
        $i = 0;
        foreach (array_keys($data) as $key) {
            $ret .= '\''.$key.'\'';
            if ($i !== \count($data) - 1) {
                $ret .= ',';
            }
            ++$i;
        }
        $ret .= ']';

        return $ret;
    }

    public function hasCapability(Page $route): bool
    {
        return $this->definition->hasCapability($route);
    }

    public function getDefinition(): DefinitionInterface
    {
        return $this->definition;
    }

    public function getTemplatePath(string $templatePath): string
    {
        if ($this->templating->getLoader()->exists($this->getDefinition()->getTemplateDirectory().'/'.$templatePath)) {
            return $this->getDefinition()->getTemplateDirectory().'/'.$templatePath;
        }

        return '@whatwedoCrud/Crud/'.$templatePath;
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
        if (EntityPreselectType::class === $formType) {
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
        if (null === $this->reflectionObject && $this->data) {
            $this->reflectionObject = new \ReflectionObject($this->data);
        }

        return $this->reflectionObject;
    }
}
