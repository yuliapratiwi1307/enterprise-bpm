@extends('layouts.app')

@section('title', 'Notifikasi Production · Enterprise BPM')
@section('eyebrow', 'Production · PROD-03')
@section('page-title', 'Notifikasi')
@section('page-description', 'Pembaruan Design Request, review, deadline, dan instruksi penghentian.')

@section('content')
    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        <section class="card">
            <div class="table-wrap"><table class="data-table">
                <thead><tr><th>Status</th><th>Notifikasi</th><th>Waktu</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($notifications as $notification)
                    <tr>
                        <td>@if($notification->read_at)<span class="status-badge">DIBACA</span>@else<span class="status-badge status-review">BARU</span>@endif</td>
                        <td><strong>{{ $notification->data['message'] ?? 'Notifikasi Production' }}</strong><p class="small muted">{{ str_replace('_', ' ', $notification->data['event'] ?? '') }}</p></td>
                        <td>{{ $notification->created_at->format('d-m-Y H:i') }}</td>
                        <td>
                            @if (!$notification->read_at)
                                <form method="POST" action="{{ route('prod03.notifications.read', $notification->id) }}">@csrf<button class="button button-secondary btn-inline" type="submit">Buka dan tandai dibaca</button></form>
                            @elseif (!empty($notification->data['details']['design_request_id']))
                                <a class="button button-secondary btn-inline" href="{{ route('prod03.design-requests.show', $notification->data['details']['design_request_id']) }}">Buka tugas</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state"><h3>Belum ada notifikasi</h3><p>Notifikasi workflow Production akan tampil di sini.</p></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div style="margin-top:16px">{{ $notifications->links() }}</div>
        </section>
    </div>
@endsection
