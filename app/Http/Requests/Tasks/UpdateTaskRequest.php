<?php

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('task')) ?? false;
    }

    public function rules(): array
    {
        /** @var Task $task */
        $task = $this->route('task');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'category' => ['sometimes', 'required', 'string', 'max:80'],
            'assignee_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('workspace_members', 'user_id')->where(fn ($query) => $query
                    ->where('workspace_id', $task->workspace_id)
                    ->where('status', 'active')),
            ],
            'due_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'status' => ['sometimes', 'required', Rule::in(['planned', 'in_progress', 'completed'])],
        ];
    }
}
