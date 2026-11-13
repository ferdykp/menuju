<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NeedCategoryController extends Controller
{
    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validator = Validator::make($request->all(), ['name' => ['required', 'string', 'max:80']]);
        $validator->after(function ($validator) use ($workspace, $request): void {
            $name = mb_strtolower(trim((string) $request->input('name')));
            if ($name !== '' && $workspace->needCategories()->whereRaw('LOWER(name) = ?', [$name])->exists()) {
                $validator->errors()->add('name', 'Kategori dengan nama tersebut sudah ada.');
            }
        });
        $data = $validator->validate();

        $category = $workspace->needCategories()->create($data);

        return response()->json(['category' => $category], 201);
    }
}
