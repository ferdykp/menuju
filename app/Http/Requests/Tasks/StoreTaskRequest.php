<?php

namespace App\Http\Requests\Tasks;

use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('workspace')) ?? false;
    }

    public function rules(): array
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:80'],
            'milestone_id' => ['nullable', 'integer', Rule::exists('milestones', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id))],
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id)->whereNull('deleted_at'))],
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('workspace_members', 'user_id')->where(fn ($query) => $query
                    ->where('workspace_id', $workspace->id)
                    ->where('status', 'active')),
            ],
            'due_at' => ['nullable', 'date_format:Y-m-d'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'status' => ['sometimes', Rule::in(['planned', 'in_progress', 'completed'])],
        ];
    }
}
