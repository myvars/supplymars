<?php

namespace App\Shared\UI\Http\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TurnstileValidator extends ConstraintValidator
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $turnstileSecretKey,
        private readonly string $kernelEnvironment,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Turnstile) {
            throw new UnexpectedTypeException($constraint, Turnstile::class);
        }

        if ($this->kernelEnvironment === 'test') {
            return;
        }

        if (empty($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        $response = $this->httpClient->request('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => [
                'secret' => $this->turnstileSecretKey,
                'response' => $value,
            ],
        ]);

        $data = $response->toArray(false);

        if (!($data['success'] ?? false)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
