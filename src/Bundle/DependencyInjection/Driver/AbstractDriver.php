<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\ResourceBundle\DependencyInjection\Driver;

use Sylius\Resource\Factory\Factory;
use Sylius\Resource\Factory\TranslatableFactoryInterface;
use Sylius\Resource\Metadata\Metadata;
use Sylius\Resource\Metadata\MetadataInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractDriver implements DriverInterface
{
    public function load(ContainerBuilder $container, MetadataInterface $metadata): void
    {
        $this->setClassesParameters($container, $metadata);

        if ($metadata->hasClass('controller')) {
            $this->addController($container, $metadata);
        }

        $this->addManager($container, $metadata);
        $this->addRepository($container, $metadata);

        if ($metadata->hasClass('factory')) {
            $this->addFactory($container, $metadata);
        }
    }

    protected function setClassesParameters(ContainerBuilder $container, MetadataInterface $metadata): void
    {
        if ($metadata->hasClass('model')) {
            $container->setParameter(sprintf('%s.model.%s.class', $metadata->getApplicationName(), $metadata->getName()), $metadata->getClass('model'));
        }
        if ($metadata->hasClass('controller')) {
            $container->setParameter(sprintf('%s.controller.%s.class', $metadata->getApplicationName(), $metadata->getName()), $metadata->getClass('controller'));
        }
        if ($metadata->hasClass('factory')) {
            $container->setParameter(sprintf('%s.factory.%s.class', $metadata->getApplicationName(), $metadata->getName()), $metadata->getClass('factory'));
        }
        if ($metadata->hasClass('repository')) {
            $container->setParameter(sprintf('%s.repository.%s.class', $metadata->getApplicationName(), $metadata->getName()), $metadata->getClass('repository'));
        }
    }

    protected function addController(ContainerBuilder $container, MetadataInterface $metadata): void
    {
        $definition = new Definition($metadata->getClass('controller'));
        $definition
            ->setPublic(true)
            ->setArguments([
                $this->getMetadataDefinition($metadata),
                new Reference('sylius.resource_controller.request_configuration_factory'),
                new Reference('sylius.resource_controller.view_handler', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference($metadata->getServiceId('repository')),
                new Reference($metadata->getServiceId('factory')),
                new Reference('sylius.resource_controller.new_resource_factory'),
                new Reference($metadata->getServiceId('manager')),
                new Reference('sylius.resource_controller.single_resource_provider'),
                new Reference('sylius.resource_controller.resources_collection_provider'),
                new Reference('sylius.resource_controller.form_factory'),
                new Reference('sylius.resource_controller.redirect_handler'),
                new Reference('sylius.resource_controller.flash_helper'),
                new Reference('sylius.resource_controller.authorization_checker'),
                new Reference('sylius.resource_controller.event_dispatcher'),
                new Reference($metadata->getServiceId('controller_state_machine'), ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference('sylius.resource_controller.resource_update_handler'),
                new Reference('sylius.resource_controller.resource_delete_handler'),
            ])
            ->addMethodCall('setContainer', [new Reference('service_container')])
            ->addTag('controller.service_arguments')
        ;

        $container->setDefinition($metadata->getServiceId('controller'), $definition);
    }

    protected function addFactory(ContainerBuilder $container, MetadataInterface $metadata): void
    {
        $factoryClass = $metadata->getClass('factory');
        $modelClass = $metadata->getClass('model');

        $definition = new Definition($factoryClass);
        $definition->setPublic(true);

        $definitionArgs = [$modelClass];

        /** @var array $factoryInterfaces */
        $factoryInterfaces = class_implements($factoryClass);
        if (in_array(TranslatableFactoryInterface::class, $factoryInterfaces, true)) {
            $decoratedDefinition = new Definition(Factory::class);
            $decoratedDefinition->setArguments($definitionArgs);

            $definitionArgs = [$decoratedDefinition, new Reference('sylius.translation_locale_provider')];
        }

        $definition->setArguments($definitionArgs);

        $container->setDefinition($metadata->getServiceId('factory'), $definition)
            ->addTag('sylius.resource_factory')
        ;

        /** @var array $factoryParents */
        $factoryParents = class_parents($factoryClass);

        $typehintClasses = array_merge(
            $factoryInterfaces,
            [$factoryClass],
            $factoryParents,
        );

        foreach ($typehintClasses as $typehintClass) {
            $container->registerAliasForArgument(
                $metadata->getServiceId('factory'),
                $typehintClass,
                $metadata->getHumanizedName() . ' factory',
            );
        }
    }

    protected function getMetadataDefinition(MetadataInterface $metadata): Definition
    {
        $definition = new Definition(Metadata::class);
        $definition
            ->setFactory([new Reference('sylius.resource_registry'), 'get'])
            ->setArguments([$metadata->getAlias()])
        ;

        return $definition;
    }

    abstract protected function addManager(ContainerBuilder $container, MetadataInterface $metadata): void;

    abstract protected function addRepository(ContainerBuilder $container, MetadataInterface $metadata): void;
}
