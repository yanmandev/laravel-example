<?php

declare(strict_types=1);

namespace App\Features\Projects\Http\Requests;

use App\Features\Projects\Dto\ProjectData;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProjectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }

    public function toData(): ProjectData
    {
        return new ProjectData(
            name: $this->string('name')->trim()->toString(),
        );
    }
}
