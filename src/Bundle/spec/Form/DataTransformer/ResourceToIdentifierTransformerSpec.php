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

namespace spec\Sylius\Bundle\ResourceBundle\Form\DataTransformer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class ResourceToIdentifierTransformerSpec extends ObjectBehavior
{
    function let(RepositoryInterface $repository): void
    {
        $this->beConstructedWith($repository, 'id');
    }

    function it_does_not_reverses_null_value(RepositoryInterface $repository): void
    {
        $repository->findOneBy(Argument::any())->shouldNotBeCalled();

        $this->reverseTransform(null)->shouldReturn(null);
    }

    function it_throws_an_exception_on_non_existing_resource(RepositoryInterface $repository): void
    {
        $repository->getClassName()->willReturn(ResourceInterface::class);
        $repository->findOneBy(['id' => 6])->willReturn(null);

        $this->shouldThrow(TransformationFailedException::class)->during('reverseTransform', [6]);
    }

    function it_reverse_transform_identifier_to_resource(RepositoryInterface $repository, ResourceInterface $resource): void
    {
        $repository->findOneBy(['id' => 5])->willReturn($resource);

        $this->reverseTransform(5)->shouldReturn($resource);
    }

    function it_transforms_null_value_to_empty_string(RepositoryInterface $repository): void
    {
        $repository->getClassName()->willReturn(ResourceInterface::class);

        $this->transform(null)->shouldReturn(null);
    }

    function it_transforms_resource_in_identifier(RepositoryInterface $repository, ResourceInterface $resource): void
    {
        $repository->getClassName()->willReturn(ResourceInterface::class);

        $resource->getId()->willReturn(6);

        $this->transform($resource)->shouldReturn(6);
    }
}
