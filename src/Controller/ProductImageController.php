<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Service\Crud\Common\CrudDeleteAction;
use App\Service\Crud\CrudHandler;
use App\Service\Product\ProductImageCreator;
use App\Service\Product\ProductImageOrderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/product')]
#[IsGranted('ROLE_ADMIN')]
class ProductImageController extends AbstractController
{
    #[Route('/{id}/images', name: 'app_product_images', methods: ['GET'])]
    public function showProductImages(Product $product): Response
    {
        return $this->render('product/images.html.twig', ['result' => $product]);
    }

    #[Route('/{id}/images/create', name: 'app_product_image_create', methods: ['POST'])]
    public function create(
        Request $request,
        Product $product,
        CrudHandler $handler,
        ProductImageCreator $action
    ): Response
    {
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($product)
                ->setCrudAction($action)
                ->setCrudActionContext(['imageFiles' => $request->files->get('imageFile')])
                ->setSuccessFlash('Product Image(s) added!')
                ->setErrorFlash('Can not add Product Image!')
                ->setSuccessLink(
                    $this->generateUrl('app_product_images', ['id' => $product->getId()])
                )
        );
    }

    #[Route('/images/{id}/remove', name: 'app_product_image_remove', methods: ['GET'])]
    public function remove(
        ProductImage $productImage,
        CrudHandler $handler,
        CrudDeleteAction $action,
    ): Response
    {
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($productImage)
                ->setCrudAction($action)
                ->setSuccessFlash('Product Image removed!')
                ->setErrorFlash('Can not remove Product Image!')
                ->setSuccessLink(
                    $this->generateUrl('app_product_images', ['id' => $productImage->getProduct()->getId()])
                )
        );
    }

    #[Route('/{id}/images/reorder', name: 'app_product_image_reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        Product $product,
        CrudHandler $handler,
        ProductImageOrderer $action
    ): Response {
        $orderedIds = json_decode($request->getContent(), true);
        if (null === $orderedIds) {
            return $this->json(['detail' => 'Invalid body'], 400);
        }

        $action->handle(
            $handler->setDefaults()
                ->setEntity($product)
                ->setCrudActionContext(['orderedIds' => $orderedIds])
        );

        return $this->json($product->getProductImages(), 200, [], ['groups' => ['main']]);
    }
}