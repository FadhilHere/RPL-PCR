@props(['html' => null])

@php
    $safeHtml = app(\App\Actions\Asesor\SanitizeCatatanAsesorAction::class)
        ->execute(is_string($html) ? $html : null);
@endphp

@if ($safeHtml)
<div {{ $attributes }}>
    {!! $safeHtml !!}
</div>
@endif
