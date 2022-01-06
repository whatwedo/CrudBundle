<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Content;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Factory\TableFactory;

class ReloadRelationContent extends RelationContent
{
    protected array $accessorPathDefinitionCacheMap = [];

    public function __construct(
        protected TableFactory $tableFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected DefinitionManager $definitionManager,
        protected RequestStack $requestStack,
        protected ManagerRegistry $doctrine,
        protected UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct(
                $tableFactory,
                $eventDispatcher,
                $authorizationChecker,
                $definitionManager,
                $requestStack,
                $doctrine,
        );
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([

            'create_url' => null,
            'reload_url' => null,
            'visibility' => [Page::SHOW],
        ]);

        $resolver->setRequired('create_url');
        $resolver->setRequired('reload_url');
        $resolver->setAllowedTypes('create_url', ['callable', 'null']);
        $resolver->setAllowedTypes('reload_url', ['callable', 'null']);

        $resolver->setDefault('reload_url', fn ($data) => $this->urlGenerator->generate(
            $this->getDefinition()::getRoute(Page::RELOAD), [
                'id' => $data->getId(),
                'field' => $this->acronym,
            ]
        ));
    }

    public function getCreateUrl($data)
    {
        if (is_callable($this->options['create_url'])) {
            return $this->options['create_url']($data);
        }
        return $this->options['create_url'];
    }

    public function getReloadUrl($data)
    {
        if (is_callable($this->options['reload_url'])) {
            return $this->options['reload_url']($data);
        }
        return $this->options['reload_url'];
    }

    /**
     * @return mixed[]
     */
    public function getActions(): array
    {
        return $this->options['actions'];
    }

}
