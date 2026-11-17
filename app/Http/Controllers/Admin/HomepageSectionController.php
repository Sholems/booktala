<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use Illuminate\Http\Request;

class HomepageSectionController extends Controller
{
    public function index()
    {
        $sections = HomepageSection::orderBy('order')->get();
        return view('admin.homepage-sections.index', compact('sections'));
    }

    public function edit(HomepageSection $homepageSection)
    {
        return view('admin.homepage-sections.edit', compact('homepageSection'));
    }

    public function update(Request $request, HomepageSection $homepageSection)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_visible' => 'nullable',
        ]);

        $validated['is_visible'] = $request->has('is_visible');

        $homepageSection->update($validated);

        return back()->with('success', 'Section updated successfully');
    }

    public function reorder(Request $request)
    {
        foreach ($request->sections as $index => $id) {
            HomepageSection::where('id', $id)->update(['order' => $index]);
        }
        return response()->json(['success' => true]);
    }
}
