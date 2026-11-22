{{-- PROD02_REFERENCE_IMAGES_ISOLATED_V1: separate from Step 4 attachments --}}
@php
    $referenceReady = \Illuminate\Support\Facades\Schema::hasTable('client_brief_reference_images');
    $referenceRows = ($brief->exists && $referenceReady)
        ? \Illuminate\Support\Facades\DB::table('client_brief_reference_images')
            ->where('client_brief_id', $brief->getKey())
            ->whereNull('deleted_at')->orderBy('id')->get()
        : collect();
@endphp
<section class="card sms-ref-card" aria-labelledby="sms-ref-heading"
    data-sms-ref-root
    @if($brief->exists && $referenceReady)
      data-upload-url="{{ route('prod02.reference-images.store', ['brief' => $brief]) }}"
    @endif>
  <div class="sms-ref-heading">
    <span class="sms-ref-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/></svg></span>
    <div><h2 id="sms-ref-heading">Gambar Referensi (Opsional)</h2><p>Gambar contoh, moodboard, atau inspirasi visual khusus Tahap 3; terpisah dari Lampiran Tahap 4.</p></div>
  </div>
  @if(!$brief->exists)
    <p class="sms-ref-info">Simpan Draf terlebih dahulu untuk menambahkan gambar referensi.</p>
  @elseif(!$referenceReady)
    <p class="sms-ref-info" role="status">Fitur gambar referensi belum aktif. Migration khusus perlu disetujui dan dijalankan terlebih dahulu.</p>
  @else
    <div class="sms-ref-picker">
      <label for="sms-ref-input" class="sms-ref-picker-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="24" height="24"><path d="M12 16V3m0 0L7 8m5-5 5 5"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/></svg><strong>Pilih gambar referensi</strong><span>JPG, PNG, WEBP · maksimum 10 MB per gambar · maksimum 10 gambar aktif</span></label>
      <input type="file" id="sms-ref-input" accept="image/jpeg,image/png,image/webp" multiple>
    </div>
    <p class="sms-ref-status" data-sms-ref-status role="status" aria-live="polite"></p>
    <div class="sms-ref-grid" data-sms-ref-grid>
      @foreach($referenceRows as $referenceRow)
        <div class="sms-ref-item" data-ref-id="{{ $referenceRow->id }}">
          <a href="{{ route('prod02.reference-images.show', ['brief' => $brief, 'image' => $referenceRow->id]) }}" target="_blank" rel="noopener" title="Lihat gambar"><img loading="lazy" alt="{{ $referenceRow->original_name }}" src="{{ route('prod02.reference-images.show', ['brief' => $brief, 'image' => $referenceRow->id]) }}"></a>
          <span class="sms-ref-filename" title="{{ $referenceRow->original_name }}">{{ $referenceRow->original_name }}</span>
          <button type="button" class="sms-ref-delete" data-ref-delete-url="{{ route('prod02.reference-images.destroy', ['brief' => $brief, 'image' => $referenceRow->id]) }}" aria-label="Hapus {{ $referenceRow->original_name }}">Hapus</button>
        </div>
      @endforeach
    </div>
  @endif
</section>
@if($brief->exists && $referenceReady)
  <script src="{{ asset('js/prod02-reference-images.js') }}" defer></script>
@endif
