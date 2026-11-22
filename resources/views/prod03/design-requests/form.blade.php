@extends('layouts.app')

@section('title', 'Design Request · Enterprise BPM')
@section('eyebrow', 'Production · PROD-03')
@section('page-title', 'Buat Design Request')
@section('page-description', 'Brief Marketing #' . $marketingBrief->id . ' · ' . $marketingBrief->clientBrief->request_number)

@section('content')
    <div class="stack">
        @if ($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
        <div class="notice notice-warning">Rekomendasi klasifikasi otomatis belum dapat ditentukan: spesifikasi FINAL belum menetapkan kriteria algoritma. Marketing menetapkan klasifikasi final beserta alasan; nilai rekomendasi sistem akan tetap tercatat kosong, bukan ditebak.</div>
        <form class="stack" method="POST" action="{{ route('prod03.design-requests.store', $marketingBrief) }}">
            @csrf
            <section class="card">
                <div class="field-grid">
                    <div class="field field-full"><label for="title">Judul pekerjaan desain</label><input id="title" name="title" value="{{ old('title') }}" required></div>
                    <div class="field field-full"><label for="description">Ruang lingkup dan hasil yang diminta</label><textarea id="description" name="description" required>{{ old('description') }}</textarea></div>
                    <div class="field"><label for="classification">Klasifikasi final Marketing</label><select id="classification" name="classification" required><option value="">Pilih klasifikasi</option><option value="CRITICAL" @selected(old('classification') === 'CRITICAL')>Kritis</option><option value="NONCRITICAL" @selected(old('classification') === 'NONCRITICAL')>Nonkritis</option></select></div>
                    <div class="field"><label for="deadline_at">Deadline awal</label><input id="deadline_at" name="deadline_at" type="datetime-local" value="{{ old('deadline_at') }}" required></div>
                    <div class="field field-full"><label for="classification_reason">Alasan klasifikasi</label><textarea id="classification_reason" name="classification_reason" rows="3" required>{{ old('classification_reason') }}</textarea></div>
                    <div class="field field-full"><label for="impact">Dampak terhadap Brief / pekerjaan</label><textarea id="impact" name="impact" rows="3" required>{{ old('impact') }}</textarea></div>
                </div>
            </section>
            <div class="actions"><button class="button" type="submit">Buat Design Request</button><a class="button button-secondary" href="{{ route('prod03.briefs.show', $marketingBrief) }}">Kembali</a></div>
        </form>
    </div>
@endsection
