<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageSectionUpdateRequest;
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

    public function update(HomepageSectionUpdateRequest $request, HomepageSection $homepageSection)
    {
        $validated = $request->validated();

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
