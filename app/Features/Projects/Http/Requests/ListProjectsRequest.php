<?php

declare(strict_types=1);

namespace App\Features\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListProjectsRequest extends FormRequest
{
    private const int DEFAULT_PER_PAGE = 15;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('perPage', self::DEFAULT_PER_PAGE);
    }
}
