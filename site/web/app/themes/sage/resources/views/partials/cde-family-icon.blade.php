{{-- El adjunto sólo aporta la silueta. El color visible lo hereda el SVG del HTML. --}}
@if (!empty($family['icon_id']))
  @php
    $attachmentId = (int) $family['icon_id'];
    $iconUrl = wp_attachment_is_image($attachmentId) ? wp_get_attachment_image_url($attachmentId, 'full') : false;
  @endphp
  @if ($iconUrl)
    @php
      $maskId = wp_unique_id("cde-family-mask-{$attachmentId}-");
    @endphp
    <svg class="{{ $iconClass }} fill-current" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
      <defs>
        <mask id="{{ $maskId }}" mask-type="alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="100" height="100">
          <image href="{{ $iconUrl }}" width="100" height="100" preserveAspectRatio="xMidYMid meet" />
        </mask>
      </defs>
      <rect width="100" height="100" mask="url(#{{ $maskId }})" />
    </svg>
  @endif
@elseif (!empty($family['icon']))
  <x-icon :name="$family['icon']" :class="$iconClass" aria-hidden="true" focusable="false" />
@endif
