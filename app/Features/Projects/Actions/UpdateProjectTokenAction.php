<?php

declare(strict_types=1);

namespace App\Features\Projects\Actions;

use App\Features\Projects\Dto\UpdateProjectTokenData;
use App\Features\Projects\Exceptions\ProjectDomainException;
use App\Features\Projects\Models\ProjectToken;

final readonly class UpdateProjectTokenAction
{
    /**
     * @throws ProjectDomainException when trying to activate an expired token
     */
    public function execute(ProjectToken $token, UpdateProjectTokenData $data): ProjectToken
    {
        if ($data->isActive === true && $token->isExpired()) {
            throw ProjectDomainException::cannotActivateExpiredToken();
        }

        if ($data->name !== null) {
            $token->name = $data->name;
        }

        if ($data->isActive !== null) {
            $token->is_active = $data->isActive;
        }

        $token->save();

        return $token;
    }
}
