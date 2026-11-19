@if(isset($sectionsArray['blog']) && $sectionsArray['blog']->is_visible)
<section class="py-8 md:py-16 bg-white">
    <div class="container mx-auto px-4 md:px-6">
        <div class="text-center mb-6 md:mb-12">
            <h2 class="text-2xl md:text-3xl font-bold text-primary mb-2 md:mb-4">{{ $sectionsArray['blog']->title ?? 'Latest Blog Posts' }}</h2>
            <p class="text-gray-600 text-sm md:text-base">{{ $sectionsArray['blog']->subtitle ?? 'Travel tips and guides for your next trip' }}</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @forelse($recentBlogs as $blog)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                <div class="relative h-48">
                    @if($blog->image)
                    <img src="{{ Str::startsWith($blog->image, 'http') ? $blog->image : url('media/' . ltrim($blog->image, '/')) }}" alt="{{ $blog->title }}" class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full bg-gray-300 flex items-center justify-center">
                        <i class="fas fa-blog text-4xl text-gray-400"></i>
                    </div>
                    @endif
                </div>
                <div class="p-6">
                    @if($blog->category)
                    <span class="text-xs font-bold text-accent uppercase">{{ $blog->category }}</span>
                    @endif
                    <h3 class="text-xl font-bold mb-2 mt-2">{{ $blog->title }}</h3>
                    <p class="text-gray-600 text-sm mb-4">{{ $blog->excerpt }}</p>
                    <a href="{{ route('blog.show', $blog->slug) }}" class="text-primary font-medium hover:underline">Read More →</a>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-8">
                <p class="text-gray-500">No blog posts yet.</p>
            </div>
            @endforelse
        </div>
        
        <div class="text-center mt-8">
            <a href="{{ route('blog.index') }}" class="inline-block px-6 py-2 border-2 border-primary text-primary rounded hover:bg-primary hover:text-white transition">View All Posts</a>
        </div>
    </div>
</section>
@endif
