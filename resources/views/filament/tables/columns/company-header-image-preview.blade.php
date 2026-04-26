@php
    use Illuminate\Support\Facades\Storage;

    $record = $getRecord();
    $isVideo = filled($record->video);
    $imageUrl = filled($record->image) ? Storage::disk('public')->url($record->image) : asset('images/slider/fashion-slideshow-05.jpg');
    $videoUrl = filled($record->video) ? Storage::disk('public')->url($record->video) : null;
@endphp

<div style="display:flex;align-items:center;gap:10px;padding:6px 0;">
    @if ($isVideo)
        <div style="width:110px;height:66px;border-radius:8px;overflow:hidden;position:relative;background:#111;">
            <video src="{{ $videoUrl }}" muted playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover;"></video>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.15);color:#fff;font-size:18px;">
                ▶
            </div>
        </div>
    @else
        <img src="{{ $imageUrl }}" alt="preview" style="width:110px;height:66px;object-fit:cover;border-radius:8px;">
    @endif
</div>
