<?php

declare(strict_types=1);

namespace App\Features\Projects\Http\Requests;

use App\Features\Projects\Dto\UpdateProjectTokenData;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateProjectTokenRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('manageTokens', $this->route('project'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:100'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    public function toData(): UpdateProjectTokenData
    {
        return new UpdateProjectTokenData(
            name: $this->has('name') ? $this->string('name')->trim()->toString() : null,
            isActive: $this->has('isActive') ? $this->boolean('isActive') : null,
        );
    }
}
