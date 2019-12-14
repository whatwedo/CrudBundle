<?php

namespace whatwedo\CrudBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use whatwedo\CrudBundle\Content\ContentInterface;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Extension\ExtensionInterface;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class whatwedoCrudExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Breadcrumbs
        if (isset($config['breadcrumbs'])
            && isset($config['breadcrumbs']['home_text'])) {
            $container->setParameter('whatwedo_crud.config.breadcrumbs.home.text', $config['breadcrumbs']['home_text']);
        } else {
            $container->setParameter('whatwedo_crud.config.breadcrumbs.home.text', 'Dashboard');
        }

        if (isset($config['breadcrumbs'])
            && isset($config['breadcrumbs']['home_route'])) {
            $container->setParameter('whatwedo_crud.config.breadcrumbs.home.route', $config['breadcrumbs']['home_route']);
        } else {
            $container->setParameter('whatwedo_crud.config.breadcrumbs.home.route', false);
        }

        // templates
        $templates = [
            'show' => '_boxes/show.html.twig',
            'create' => '_boxes/create.html.twig',
            'edit' => '_boxes/edit.html.twig'
        ];
        if (isset($config['templates'])) {
            $templates = $config['templates'];
        }
        $container->setParameter('whatwedo_crud.config.templates', $templates);

        $container->registerForAutoconfiguration(ContentInterface::class)->addTag('crud.content');
        $container->registerForAutoconfiguration(ExtensionInterface::class)->addTag('crud.extension');
        $container->registerForAutoconfiguration(DefinitionInterface::class)->addTag('crud.definition');

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
