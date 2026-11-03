<?php

namespace App\Http\Controllers;

use App\Models\CategoryTag;
use Illuminate\Http\Request;

class CategoryTagController extends Controller
{
    public function index(Request $request)
    {
        $tags = CategoryTag::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();
        return response()->json($tags);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);
        $data['user_id'] = $request->user()->id;
        $data['color'] = $data['color'] ?? '#6B7280';

        $tag = CategoryTag::firstOrCreate(
            ['user_id' => $data['user_id'], 'name' => $data['name']],
            $data
        );
        return response()->json($tag, 201);
    }

    public function destroy(Request $request, CategoryTag $categoryTag)
    {
        if ($categoryTag->user_id !== $request->user()->id) {
            abort(403);
        }
        $categoryTag->delete();
        return response()->json(['ok' => true]);
    }
}
