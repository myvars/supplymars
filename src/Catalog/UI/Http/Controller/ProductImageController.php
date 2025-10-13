<?php

namespace App\Catalog\UI\Http\Controller;

use App\Catalog\Application\Command\ProductImage\CreateProductImage;
use App\Catalog\Application\Command\ProductImage\DeleteProductImage;
use App\Catalog\Application\Command\ProductImage\ReorderProductImage;
use App\Catalog\Application\Handler\ProductImage\CreateProductImageHandler;
use App\Catalog\Application\Handler\ProductImage\DeleteProductImageHandler;
use App\Catalog\Application\Handler\ProductImage\ReorderProductImageHandler;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ProductImageController extends AbstractController
{
    #[Route(path: '/product_image/{id}/images', name: 'app_catalog_product_image_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] Product $product): Response {
        return $this->render('/catalog/product_image/show.html.twig', ['result' => $product]);
    }

    #[Route(path: '/product/{id}/images/create', name: 'app_catalog_product_image_create', methods: ['POST'])]
    public function create(
        Request $request,
        #[ValueResolver('public_id')] Product $product,
        CreateProductImageHandler $handler,
        CommandFlow $flow,
    ): Response {
        $response = $flow->process(
            request: $request,
            command: new CreateProductImage($product->getPublicId(),
                $request->files->get('imageFile') ?? []
            ),
            handler: $handler,
            context: FlowContext::forSuccess('app_catalog_product_image_show', [
                'id' => $product->getPublicId()->value()
            ]),
        );

        if ($request->isXmlHttpRequest()) {
            return $this->json($product->getProductImages(), 200, [], ['groups' => ['main']]);
        }

        return $response;
    }

    #[Route(path: '/product/images/{id}/remove', name: 'app_catalog_product_image_remove', methods: ['GET'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] ProductImage $productImage,
        DeleteProductImageHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new DeleteProductImage($productImage->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_catalog_product_image_show', [
                'id' => $productImage->getProduct()->getPublicId()->value()
            ]),
        );
    }

    #[Route(path: '/product/{id}/images/reorder', name: 'app_catalog_product_image_reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        #[ValueResolver('public_id')] Product $product,
        ReorderProductImageHandler $handler,
        CommandFlow $flow,
    ): Response {
        $imageIdOrder = json_decode($request->getContent(), true);
        if (null === $imageIdOrder) {
            return $this->json(['detail' => 'Invalid body'], 400);
        }

        $response = $flow->process(
            request: $request,
            command: new ReorderProductImage($product->getPublicId(), array_flip($imageIdOrder)),
            handler: $handler,
            context: FlowContext::forSuccess('app_catalog_product_image_show', [
                'id' => $product->getPublicId()->value()
            ]),
        );

        return $this->json($product->getProductImages(), 200, [], ['groups' => ['main']]);
    }
}
