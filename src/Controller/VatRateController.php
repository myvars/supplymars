<?php

namespace App\Controller;

use App\Entity\VatRate;
use App\Form\VatRateType;
use App\Repository\VatRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/vat-rate')]
class VatRateController extends AbstractController
{
    CONST string SECTION = 'VAT Rate';

    #[Route('/', name: 'app_vat_rate_index', methods: ['GET'])]
    public function index(
        VatRateRepository $vatRateRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($vatRateRepository->findBySearchQueryBuilder($query, $sort, $sortDirection)),
            $page,
            $limit
        );

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'index',
            'results' => $pager,
        ]);
    }

    #[Route('/new', name: 'app_vat_rate_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vatRate = new VatRate();
        $form = $this->createForm(VatRateType::class, $vatRate, [
            'action' => $this->generateUrl('app_vat_rate_new')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vatRate);
            $entityManager->flush();

            $this->addFlash('success', 'New '.self::SECTION.' added!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_vat_rate_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'new',
            'result' => $vatRate,
            'form' => $form,
            'formColumns' => 2
        ]);
    }

    #[Route('/{id}', name: 'app_vat_rate_show', methods: ['GET'])]
    public function show(VatRate $vatRate): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'show',
            'result' => $vatRate,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vat_rate_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, VatRate $vatRate, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VatRateType::class, $vatRate, [
            'action' => $this->generateUrl('app_vat_rate_edit', ['id' => $vatRate->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' updated!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_vat_rate_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'edit',
            'result' => $vatRate,
            'form' => $form,
            'formColumns' => 2
        ]);
    }

    #[Route('/{id}/delete', name: 'app_vat_rate_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(VatRate $vatRate): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'delete',
            'result' => $vatRate,
        ]);
    }

    #[Route('/{id}', name: 'app_vat_rate_delete', methods: ['POST'])]
    public function delete(Request $request, VatRate $vatRate, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vatRate->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vatRate);
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' deleted!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }
        }

        return $this->redirectToRoute('app_vat_rate_index', [], Response::HTTP_SEE_OTHER);
    }
}
