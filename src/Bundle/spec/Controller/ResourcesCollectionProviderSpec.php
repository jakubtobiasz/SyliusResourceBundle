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

namespace spec\Sylius\Bundle\ResourceBundle\Controller;

use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Hateoas\Representation\PaginatedRepresentation;
use Pagerfanta\Pagerfanta;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\ResourcesCollectionProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourcesResolverInterface;
use Sylius\Bundle\ResourceBundle\Grid\View\ResourceGridView;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

final class ResourcesCollectionProviderSpec extends ObjectBehavior
{
    function let(ResourcesResolverInterface $resourcesResolver, PagerfantaFactory $pagerfantaRepresentationFactory): void
    {
        $this->beConstructedWith($resourcesResolver, $pagerfantaRepresentationFactory);
    }

    function it_implements_resources_collection_provider_interface(): void
    {
        $this->shouldImplement(ResourcesCollectionProviderInterface::class);
    }

    function it_returns_resources_resolved_from_repository(
        ResourcesResolverInterface $resourcesResolver,
        RequestConfiguration $requestConfiguration,
        RepositoryInterface $repository,
        ResourceInterface $firstResource,
        ResourceInterface $secondResource,
    ): void {
        $requestConfiguration->isHtmlRequest()->willReturn(true);

        $resourcesResolver->getResources($requestConfiguration, $repository)->willReturn([$firstResource, $secondResource]);

        $this->get($requestConfiguration, $repository)->shouldReturn([$firstResource, $secondResource]);
    }

    function it_handles_Pagerfanta(
        ResourcesResolverInterface $resourcesResolver,
        RequestConfiguration $requestConfiguration,
        RepositoryInterface $repository,
        Pagerfanta $paginator,
        Request $request,
        ParameterBag $queryParameters,
    ): void {
        $requestConfiguration->isHtmlRequest()->willReturn(true);
        $requestConfiguration->getPaginationMaxPerPage()->willReturn(5);

        $resourcesResolver->getResources($requestConfiguration, $repository)->willReturn($paginator);

        $requestConfiguration->getRequest()->willReturn($request);
        $request->query = $queryParameters;
        $queryParameters->has('limit')->willReturn(true);
        $queryParameters->getInt('limit')->willReturn(5);
        $queryParameters->get('page', 1)->willReturn(6);

        $paginator->setMaxPerPage(5)->shouldBeCalled();
        $paginator->setCurrentPage(6)->shouldBeCalled();
        $paginator->getCurrentPageResults()->willReturn([]);

        $this->get($requestConfiguration, $repository)->shouldReturn($paginator);
    }

    function it_restricts_max_pagination_limit_based_on_grid_configuration(
        ResourcesResolverInterface $resourcesResolver,
        RequestConfiguration $requestConfiguration,
        RepositoryInterface $repository,
        ResourceGridView $gridView,
        Grid $grid,
        Pagerfanta $paginator,
        Request $request,
        ParameterBag $queryParameters,
    ): void {
        $requestConfiguration->isHtmlRequest()->willReturn(true);
        $requestConfiguration->getPaginationMaxPerPage()->willReturn(1000);

        $grid->getLimits()->willReturn([10, 20, 99]);

        $gridView->getDefinition()->willReturn($grid);
        $gridView->getData()->willReturn($paginator);

        $resourcesResolver->getResources($requestConfiguration, $repository)->willReturn($gridView);

        $requestConfiguration->getRequest()->willReturn($request);
        $request->query = $queryParameters;
        $queryParameters->has('limit')->willReturn(true);
        $queryParameters->getInt('limit')->willReturn(1000);
        $queryParameters->get('page', 1)->willReturn(1);

        $paginator->setMaxPerPage(99)->shouldBeCalled();
        $paginator->setCurrentPage(1)->shouldBeCalled();
        $paginator->getCurrentPageResults()->willReturn([]);

        $this->get($requestConfiguration, $repository)->shouldReturn($gridView);
    }

    function it_creates_a_paginated_representation_for_pagerfanta_for_non_html_requests(
        ResourcesResolverInterface $resourcesResolver,
        RequestConfiguration $requestConfiguration,
        RepositoryInterface $repository,
        Pagerfanta $paginator,
        Request $request,
        ParameterBag $queryParameters,
        ParameterBag $requestAttributes,
        PagerfantaFactory $pagerfantaRepresentationFactory,
        PaginatedRepresentation $paginatedRepresentation,
    ): void {
        $requestConfiguration->isHtmlRequest()->willReturn(false);
        $requestConfiguration->getPaginationMaxPerPage()->willReturn(8);

        $resourcesResolver->getResources($requestConfiguration, $repository)->willReturn($paginator);

        $requestConfiguration->getRequest()->willReturn($request);
        $request->query = $queryParameters;
        $queryParameters->has('limit')->willReturn(true);
        $queryParameters->getInt('limit')->willReturn(8);
        $queryParameters->get('page', 1)->willReturn(6);
        $queryParameters->all()->willReturn(['foo' => 2, 'bar' => 15]);

        $request->attributes = $requestAttributes;
        $requestAttributes->get('_route')->willReturn('sylius_product_index');
        $requestAttributes->get('_route_params')->willReturn(['slug' => 'foo-bar']);

        $paginator->setMaxPerPage(8)->shouldBeCalled();
        $paginator->setCurrentPage(6)->shouldBeCalled();
        $paginator->getCurrentPageResults()->willReturn([]);

        $pagerfantaRepresentationFactory->createRepresentation($paginator, Argument::type(Route::class))->willReturn($paginatedRepresentation);

        $this->get($requestConfiguration, $repository)->shouldReturn($paginatedRepresentation);
    }

    function it_handles_resource_grid_view(
        ResourcesResolverInterface $resourcesResolver,
        RequestConfiguration $requestConfiguration,
        RepositoryInterface $repository,
        ResourceGridView $resourceGridView,
        Grid $grid,
        Pagerfanta $paginator,
        Request $request,
        ParameterBag $queryParameters,
    ): void {
        $requestConfiguration->isHtmlRequest()->willReturn(true);
        $requestConfiguration->getPaginationMaxPerPage()->willReturn(5);

        $resourcesResolver->getResources($requestConfiguration, $repository)->willReturn($resourceGridView);
        $resourceGridView->getData()->willReturn($paginator);

        $grid->getLimits()->willReturn([10, 25, 50]);
        $resourceGridView->getDefinition()->willReturn($grid);

        $requestConfiguration->getRequest()->willReturn($request);
        $request->query = $queryParameters;
        $queryParameters->has('limit')->willReturn(true);
        $queryParameters->getInt('limit')->willReturn(5);
        $queryParameters->get('page', 1)->willReturn(6);

        $paginator->setMaxPerPage(5)->shouldBeCalled();
        $paginator->setCurrentPage(6)->shouldBeCalled();
        $paginator->getCurrentPageResults()->willReturn([]);

        $this->get($requestConfiguration, $repository)->shouldReturn($resourceGridView);
    }
}
