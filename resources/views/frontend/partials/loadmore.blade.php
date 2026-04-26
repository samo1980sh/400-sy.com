@if ($products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $products->hasMorePages())
    <div class="tf-pagination-wrap view-more-button text-center tf-pagination-btn">
        <button
            type="button"
            class="tf-btn btn-outline btn-md animate-hover-btn btn-loadmore-ajax justify-content-center"
            data-loadmore-url="{{ $products->nextPageUrl() }}"
        >
            <span class="text">{{ app()->getLocale() === 'ar' ? 'تحميل المزيد' : 'Load more' }}</span>
        </button>
    </div>
@endif
