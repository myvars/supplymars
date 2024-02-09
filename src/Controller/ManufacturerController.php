<?php

namespace App\Controller;

use App\Entity\Manufacturer;
use App\Form\ManufacturerType;
use App\Repository\ManufacturerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/manufacturer')]
class ManufacturerController extends AbstractController
{
    CONST string SECTION = 'Manufacturer';

    #[Route('/', name: 'app_manufacturer_index', methods: ['GET'])]
    public function index(
        ManufacturerRepository $manufacturerRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($manufacturerRepository->findBySearchQueryBuilder($query, $sort, $sortDirection)),
            $page,
            $limit
        );

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'index',
            'results' => $pager,
        ]);
    }

    #[Route('/new', name: 'app_manufacturer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $manufacturer = new Manufacturer();
        $form = $this->createForm(ManufacturerType::class, $manufacturer, [
            'action' => $this->generateUrl('app_manufacturer_new')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($manufacturer);
            $entityManager->flush();

            $this->addFlash('success', 'New '.self::SECTION.' added!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_manufacturer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'new',
            'result' => $manufacturer,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name: 'app_manufacturer_show', methods: ['GET'])]
    public function show(Manufacturer $manufacturer): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'show',
            'result' => $manufacturer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_manufacturer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Manufacturer $manufacturer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ManufacturerType::class, $manufacturer, [
            'action' => $this->generateUrl('app_manufacturer_edit', ['id' => $manufacturer->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' updated!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_manufacturer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'edit',
            'result' => $manufacturer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_manufacturer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(Manufacturer $manufacturer): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'delete',
            'result' => $manufacturer
        ]);
    }

    #[Route('/{id}', name: 'app_manufacturer_delete', methods: ['POST'])]
    public function delete(Request $request, Manufacturer $manufacturer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$manufacturer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($manufacturer);
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' deleted!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }
        }

        return $this->redirectToRoute('app_manufacturer_index', [], Response::HTTP_SEE_OTHER);
    }
}
