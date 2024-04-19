<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Service\CrudHelper;
use App\Service\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/product')]
class ProductImageController extends AbstractController
{
    public function __construct(
        private readonly CrudHelper $crudHelper,
        private readonly UploadHelper $uploadHelper,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%app.product_uploads%')]
        private readonly string $appProductUploads,
    ) {
    }

    #[Route('/{id}/images', name: 'app_product_images', methods: ['GET'])]
    public function showProductImages(
        ?Product $product,
        EntityManagerInterface $entityManager,
        UploadHelper $uploadHelper,
    ): Response {
        if (!$product) {
            return $this->crudHelper->renderShowEmpty('Product');
        }

        return $this->render('product/images.html.twig', [
            'result' => $product,
        ]);
    }

    #[Route('/{id}/images/create', name: 'app_product_image_create', methods: ['POST'])]
    public function create(Request $request, ?Product $product, ValidatorInterface $validator): Response
    {
        $nextPosition = $this->getNextPosition($product);
        foreach ($request->files->get('imageFile') as $imageFile) {
            $productImage = new ProductImage();
            $productImage->setProduct($product);
            $productImage->setImageFile($imageFile);
            $productImage->setPosition($nextPosition);
            $errors = $validator->validate($productImage);
            if (count($errors) > 0) {
                $this->addFlash(
                    'error',
                    'Image could not be uploaded! Please check the file type and try again.'
                );
                continue;
            }

            $this->createProductImage($productImage);
            $this->addFlash(
                'success',
                'New Product Image added!'
            );
            ++$nextPosition;
        }

        if ($request->headers->has('turbo-frame')) {
            return $this->crudHelper->streamRefresh();
        }

        return $this->redirectToRoute('app_product_images', [
            'id' => $product->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/images/{id}/remove', name: 'app_product_image_remove', methods: ['GET'])]
    public function remove(Request $request, ?ProductImage $productImage): Response
    {
        $product = $productImage->getProduct();
        $this->entityManager->remove($productImage);
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            'Product Image removed!'
        );

        if ($request->headers->has('turbo-frame')) {
            return $this->crudHelper->streamRefresh();
        }

        return $this->redirectToRoute('app_product_images', [
            'id' => $product->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/images/reorder', name: 'app_product_image_reorder', methods: ['POST'])]
    public function reorder(Request $request, ?Product $product): Response
    {
        $orderedIds = json_decode($request->getContent(), true);
        if (null === $orderedIds) {
            return $this->json(['detail' => 'Invalid body'], 400);
        }
        // from (position)=>(id) to (id)=>(position)
        $orderedIds = array_flip($orderedIds);
        foreach ($product->getProductImages() as $productImage) {
            $newPosition = (int) $orderedIds[$productImage->getId()] + 1;
            $productImage->setPosition($newPosition);
        }
        $this->entityManager->flush();

        return $this->json(
            $product->getProductImages(),
            200,
            [],
            [
                'groups' => ['main'],
            ]
        );
    }

    private function createProductImage(ProductImage $productImage): void
    {
        $productImage->setImageName(
            $this->uploadHelper->uploadFile($productImage->getImageFile(), $this->appProductUploads)
        );
        $this->entityManager->persist($productImage);
        $this->entityManager->flush();
    }

    private function getNextPosition(mixed $product): int
    {
        return $this->entityManager->getRepository(ProductImage::class)
            ->getNextPositionForProduct($product);
    }
}