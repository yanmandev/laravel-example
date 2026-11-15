<?php

declare(strict_types=1);

namespace App\Features\Projects\Actions;

use App\Features\Projects\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListProjectsAction
{
    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function execute(User $owner, int $perPage): LengthAwarePaginator
    {
        return $owner->projects()
            ->latest('id')
            ->paginate($perPage);
    }
}
