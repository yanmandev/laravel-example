<?php

declare(strict_types=1);

namespace App\Features\Projects\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Violation of a business rule of the Projects module.
 *
 * Actions throw it regardless of the transport; the HTTP layer gets a 422
 * in the same shape as a regular validation error.
 */
final class ProjectDomainException extends DomainException
{
    private function __construct(
        string $message,
        public readonly string $field,
    ) {
        parent::__construct($message);
    }

    public static function projectLimitExceeded(int $limit): self
    {
        return new self("You can't own more than {$limit} projects.", 'name');
    }

    public static function tokenLimitExceeded(int $limit): self
    {
        return new self("A project can't have more than {$limit} tokens.", 'name');
    }

    public static function cannotActivateExpiredToken(): self
    {
        return new self('An expired token cannot be activated.', 'isActive');
    }

    public function render(): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
            'errors' => [$this->field => [$this->getMessage()]],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
