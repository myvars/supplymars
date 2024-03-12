<?php

namespace App\Controller;

use App\Entity\ProductImage;
use App\Form\ProductImageType;
use App\Repository\ProductImageRepository;
use App\Service\CrudHelper;
use App\Service\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product-image')]
class ProductImageController extends AbstractController
{
    CONST string SECTION = 'Product Image';

    public function __construct(
        private readonly CrudHelper $crudHelper,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%app.product_uploads%')]
        private readonly string $appProductUploads,
    )
    {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/', name: 'app_product_image_index', methods: ['GET'])]
    public function index(
        ProductImageRepository $productImageRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'product.id', 'imageName', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $productImageRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_product_image_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ?productImage $productImage, UploadHelper $uploadHelper): Response
    {
        $form = $this->createForm(ProductImageType::class, $productImage, [
            'action' => $this->generateUrl('app_product_image_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productImage = $form->getData();
            $productImage->setImageName(
                $uploadHelper->uploadFile($productImage->getImageFile(), $this->appProductUploads)
            );

            $this->entityManager->persist($productImage);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'New Product Image added!'
            );

            if ($request->headers->has('turbo-frame')) {
                return $this->crudHelper->streamRefresh($request);
            }

            return $this->redirectToRoute('app_product_image_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product_image/new.html.twig', [
            'result' => $productImage,
            'form' => $form,
            'formColumns' => 1
        ]);
    }

    #[Route('/{id}', name: 'app_product_image_show', methods: ['GET'])]
    public function show(?ProductImage $productImage): Response
    {
        return $this->crudHelper->renderShow($productImage);
    }

    #[Route('/{id}/delete', name: 'app_product_image_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?ProductImage $productImage): Response
    {
        return $this->crudHelper->renderDeleteConfirm($productImage);
    }

    #[Route('/{id}', name: 'app_product_image_delete', methods: ['POST'])]
    public function delete(Request $request, ?ProductImage $productImage,): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $productImage,
        );
    }
}
