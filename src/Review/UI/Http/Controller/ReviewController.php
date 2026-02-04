<?php

namespace App\Review\UI\Http\Controller;

use App\Review\Application\Command\ApproveReview;
use App\Review\Application\Command\DeleteReview;
use App\Review\Application\Command\HideReview;
use App\Review\Application\Command\RepublishReview;
use App\Review\Application\Handler\ApproveReviewHandler;
use App\Review\Application\Handler\CreateReviewHandler;
use App\Review\Application\Handler\DeleteReviewHandler;
use App\Review\Application\Handler\HideReviewHandler;
use App\Review\Application\Handler\RejectReviewHandler;
use App\Review\Application\Handler\RepublishReviewHandler;
use App\Review\Application\Handler\ReviewFilterHandler;
use App\Review\Application\Handler\UpdateReviewHandler;
use App\Review\Application\Search\ReviewSearchCriteria;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Repository\ReviewRepository;
use App\Review\UI\Http\Form\Mapper\CreateReviewMapper;
use App\Review\UI\Http\Form\Mapper\RejectReviewMapper;
use App\Review\UI\Http\Form\Mapper\ReviewFilterMapper;
use App\Review\UI\Http\Form\Mapper\UpdateReviewMapper;
use App\Review\UI\Http\Form\Model\RejectReviewForm;
use App\Review\UI\Http\Form\Model\ReviewForm;
use App\Review\UI\Http\Form\Type\EditReviewType;
use App\Review\UI\Http\Form\Type\RejectReviewType;
use App\Review\UI\Http\Form\Type\ReviewFilterType;
use App\Review\UI\Http\Form\Type\ReviewType;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ReviewController extends AbstractController
{
    public const string MODEL = 'review';

    #[Route(path: '/review/', name: 'app_review_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        ReviewRepository $repository,
        #[MapQueryString] ReviewSearchCriteria $criteria = new ReviewSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch(self::MODEL),
        );
    }

    #[Route(path: '/review/search/filter', name: 'app_review_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        ReviewFilterMapper $mapper,
        ReviewFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] ReviewSearchCriteria $criteria = new ReviewSearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: ReviewFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter(self::MODEL),
        );
    }

    #[Route(path: '/review/new', name: 'app_review_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateReviewMapper $mapper,
        CreateReviewHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: ReviewType::class,
            data: new ReviewForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate(self::MODEL),
        );
    }

    #[Route(path: '/review/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] ProductReview $review,
        UpdateReviewMapper $mapper,
        UpdateReviewHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: EditReviewType::class,
            data: ReviewForm::fromEntity($review),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->allowDelete(true)
                ->successRoute('app_review_show', ['id' => $review->getPublicId()->value()])
        );
    }

    #[Route(path: '/review/{id}/delete/confirm', name: 'app_review_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] ProductReview $review,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $review,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/review/{id}/delete', name: 'app_review_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] ProductReview $review,
        DeleteReviewHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteReview($review->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/review/{id}', name: 'app_review_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] ProductReview $review): Response
    {
        return $this->render('review/show.html.twig', ['result' => $review]);
    }

    #[Route(path: '/review/{id}/approve', name: 'app_review_approve', methods: ['GET'])]
    public function approve(
        Request $request,
        #[ValueResolver('public_id')] ProductReview $review,
        ApproveReviewHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new ApproveReview($review->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_review_show', ['id' => $review->getPublicId()->value()]),
        );
    }

    #[Route(path: '/review/{id}/reject', name: 'app_review_reject', methods: ['GET', 'POST'])]
    public function reject(
        Request $request,
        #[ValueResolver('public_id')] ProductReview $review,
        RejectReviewMapper $mapper,
        RejectReviewHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: RejectReviewType::class,
            data: RejectReviewForm::fromEntity($review),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->template('review/reject.html.twig')
                ->successRoute('app_review_show', ['id' => $review->getPublicId()->value()]),
        );
    }

    #[Route(path: '/review/{id}/hide', name: 'app_review_hide', methods: ['GET'])]
    public function hide(
        Request $request,
        #[ValueResolver('public_id')] ProductReview $review,
        HideReviewHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new HideReview($review->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_review_show', ['id' => $review->getPublicId()->value()]),
        );
    }

    #[Route(path: '/review/{id}/republish', name: 'app_review_republish', methods: ['GET'])]
    public function republish(
        Request $request,
        #[ValueResolver('public_id')] ProductReview $review,
        RepublishReviewHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new RepublishReview($review->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_review_show', ['id' => $review->getPublicId()->value()]),
        );
    }
}
