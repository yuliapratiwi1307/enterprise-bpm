@extends('layouts.app')

@section('title', 'Task Pool Desain · Enterprise BPM')
@section('eyebrow', 'Production · PROD-03')
@section('page-title', 'Task Pool Desain')
@section('page-description', 'Ambil tugas yang tersedia dan kelola tugas aktif Anda.')

@section('content')
    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        @if ($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
        <section class="card">
            <div class="table-wrap"><table class="data-table">
                <thead><tr><th>Design Request</th><th>Brief / Klien</th><th>Klasifikasi</th><th>Status</th><th>Deadline</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td><a href="{{ route('prod03.design-requests.show', $task) }}">#{{ $task->id }} · {{ $task->title }}</a></td>
                        <td>Brief #{{ $task->marketing_brief_id }} · {{ $task->marketingBrief->clientBrief->client_name }}</td>
                        <td>{{ $task->classification }} @if($task->is_overdue)<span class="status-badge status-cancelled">OVERDUE</span>@endif</td>
                        <td><span class="status-badge status-{{ strtolower(str_replace('_', '-', $task->status)) }}">{{ str_replace('_', ' ', $task->status) }}</span></td>
                        <td>{{ $task->deadline_at?->format('d-m-Y H:i') ?? '—' }}</td>
                        <td>
                            @if ($task->status === 'AVAILABLE')
                                <form method="POST" action="{{ route('prod03.task-pool.take', $task) }}">@csrf<button class="button btn-inline" type="submit">Take Task</button></form>
                            @else
                                <a class="button button-secondary btn-inline" href="{{ route('prod03.design-requests.show', $task) }}">Buka tugas</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><h3>Task Pool kosong</h3><p>Tugas tersedia akan muncul di sini.</p></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div style="margin-top:16px">{{ $tasks->links() }}</div>
        </section>
    </div>
@endsection
