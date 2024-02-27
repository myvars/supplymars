<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\CategoryCostType;
use App\Form\ProductCostType;
use App\Form\ProductType;
use App\Form\SubcategoryCostType;
use App\Repository\ProductRepository;
use App\Service\CrudHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/product')]
class ProductController extends AbstractController
{
    CONST string SECTION = 'Product';
    const int FORM_COLUMNS = 2;

    public function __construct(
        private readonly CrudHelper $crudHelper,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name', 'cost', 'sellPrice', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $productRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new Product(),
            ProductType::class,
        );
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(?Product $product): Response
    {
//        #[MapEntity(expr: 'repository.findFullProduct(id)')]
        return $this->crudHelper->renderShow($product);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Product $product): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $product,
            ProductType::class,
        );
    }

    #[Route('/{id}/delete', name: 'app_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Product $product): Response
    {
        return $this->crudHelper->renderDeleteConfirm($product);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, ?Product $product): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $product,
        );
    }

    #[Route('/{id}/cost', name: 'app_product_cost', methods: ['GET'])]
    public function productCost(?Product $product, Request $request): Response
    {
        return $this->render('product/cost.html.twig', [
            'result' => $product,
        ]);
    }

    #[Route('/{id}/cost/edit', name: 'app_product_cost_edit', methods: ['GET', 'POST'])]
    public function productCostEdit(?Product $product, Request $request): Response
    {
        $form = $this->createForm(ProductCostType::class, $product, [
            'action' => $this->generateUrl(
                'app_product_cost_edit',
                ['id' => $product->getId()]
            )
        ]);

        return $this->renderCostUpdate(
            self::SECTION,
            $product,
            $request,
            $product,
            $form
        );
    }

    #[Route('/{id}/cost/category/edit', name: 'app_product_cost_category_edit', methods: ['GET', 'POST'])]
    public function productCostCategoryEdit(?Product $product, Request $request): Response
    {
        $category = $product->getCategory();

        $form = $this->createForm(CategoryCostType::class, $category, [
            'action' => $this->generateUrl(
                'app_product_cost_category_edit',
                ['id' => $product->getId()]
            )
        ]);

        return $this->renderCostUpdate(
            'Category',
            $product,
            $request,
            $category,
            $form
        );
    }

    #[Route('/{id}/cost/subcategory/edit', name: 'app_product_cost_subcategory_edit', methods: ['GET', 'POST'])]
    public function productCostSubcategoryEdit(?Product $product, Request $request): Response
    {
        $subcategory = $product->getSubcategory();

        $form = $this->createForm(SubcategoryCostType::class, $subcategory, [
            'action' => $this->generateUrl(
                'app_product_cost_subcategory_edit',
                ['id' => $product->getId()]
            )
        ]);

        return $this->renderCostUpdate(
            'Subcategory',
            $product,
            $request,
            $subcategory,
            $form
        );
    }

    private function renderCostUpdate(
        string $section,
        Product $product,
        Request $request,
        object $entity,
        FormInterface $form
    ): Response
    {
        $successResponse = $this->redirectToRoute(
            'app_product_cost',
            ['id' => $product->getId()],
            Response::HTTP_SEE_OTHER
        );

        return $this->crudHelper->renderCustomUpdate(
            $section,
            $request,
            $entity,
            $form,
            $successResponse,
            $this->generateUrl('app_product_cost', ['id' => $product->getId()])
        );
    }
}