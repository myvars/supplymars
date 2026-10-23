<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Security;

use App\Customer\Domain\Model\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A staff account can be edited only by a super admin; any other account by whoever reached the
 * page (the customer routes already require ROLE_ADMIN). Asked by UpdateCustomerHandler and by the
 * routes and templates that edit a customer.
 *
 * @extends Voter<string, User>
 */
final class CustomerVoter extends Voter
{
    public const string EDIT = 'CUSTOMER_EDIT';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::EDIT && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if ($subject->isStaff() && !$this->accessDecisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
            $vote?->addReason('Staff accounts can be edited only by a super admin.');

            return false;
        }

        return true;
    }
}
