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

namespace Sylius\Bundle\ResourceBundle\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\Metadata\MetadataInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

final class ResourceToIdentifierType extends AbstractType
{
    private RepositoryInterface $repository;

    private MetadataInterface $metadata;

    public function __construct(RepositoryInterface $repository, MetadataInterface $metadata)
    {
        $this->repository = $repository;
        $this->metadata = $metadata;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $identifier = $options['identifier'];
        Assert::nullOrString($identifier);

        $builder->addModelTransformer(
            new ResourceToIdentifierTransformer($this->repository, $identifier),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'identifier' => 'id',
            ])
            ->setAllowedTypes('identifier', 'string')
        ;
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return sprintf('%s_%s_to_identifier', $this->metadata->getApplicationName(), $this->metadata->getName());
    }
}
