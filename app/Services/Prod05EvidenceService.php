<?php

namespace App\Services;

use App\Models\Prod05ActivityControl;
use App\Models\Prod05ActivityEvidence;
use App\Models\Prod05Validation;
use App\Models\Prod05ValidationEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class Prod05EvidenceService
{
    public function __construct(
        private readonly BpmRoleAuthorization $roles
    ) {}

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'evidence' => $message,
        ]);
    }

    private function fileHash(
        Prod05ActivityControl $activity,
        string $reference
    ): string {
        $prefix = 'prod05/evidence/'
            .$activity->validation_id.'/'
            .$activity->id.'/';

        if (! str_starts_with($reference, $prefix)) {
            $this->reject('Lokasi dokumen bukti tidak valid.');
        }

        $filename = substr($reference, strlen($prefix));

        if (! preg_match(
            '/\A[a-f0-9]{32}\.(pdf|png|jpg|jpeg)\z/D',
            $filename
        )) {
            $this->reject('Nama atau format dokumen tidak valid.');
        }

        $disk = Storage::disk('local');
        $path = $disk->path($reference);
        $root = realpath($disk->path(''));

        if (! $disk->exists($reference)
            || ! is_file($path)
            || is_link($path)
            || $root === false) {
            $this->reject('File bukti tidak tersedia atau tidak aman.');
        }

        $real = realpath($path);

        if ($real === false
            || ! str_starts_with($real, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            $this->reject('Dokumen berada di luar penyimpanan privat.');
        }

        $hash = hash_file('sha256', $real);

        if ($hash === false) {
            $this->reject('Checksum dokumen gagal dihitung.');
        }

        return $hash;
    }

    private function currentRevision(Prod05Validation $validation): int
    {
        $revision = $validation->briefVersion?->revision;

        if (! is_numeric($revision) || (int) $revision < 1) {
            $this->reject('Revisi brief tidak valid.');
        }

        return (int) $revision;
    }

    private function audit(
        Prod05ActivityControl $activity,
        Prod05ActivityEvidence $evidence,
        User $actor,
        string $event,
        string $role,
        string $reason
    ): void {
        Prod05ValidationEvent::query()->create([
            'validation_id' => $activity->validation_id,
            'actor_user_id' => $actor->id,
            'actor_role' => $role,
            'event_type' => $event,
            'after_data' => [
                'activity_key' => $activity->activity_key,
                'evidence_id' => $evidence->id,
                'brief_revision' => $evidence->brief_revision,
                'file_sha256' => $evidence->file_sha256,
                'verification_status' => $evidence->verification_status,
            ],
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
    }

    public function submit(
        Prod05ActivityControl $activity,
        User $actor,
        string $reference,
        string $type,
        string $description
    ): Prod05ActivityEvidence {
        $this->roles->require($actor, 'ASST_MANAGER_PRODUKSI');

        $type = trim($type);
        $description = trim($description);

        if ($type === '' || mb_strlen($type) > 60
            || $description === '' || mb_strlen($description) > 5000) {
            $this->reject('Jenis atau keterangan bukti tidak valid.');
        }

        return DB::transaction(function () use (
            $activity, $actor, $reference, $type, $description
        ) {
            $validation = Prod05Validation::query()
                ->whereKey($activity->validation_id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked = Prod05ActivityControl::query()
                ->whereKey($activity->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->validation_id !== (int) $validation->id
                || $locked->control_status !== 'ON_HOLD') {
                $this->reject('Aktivitas tidak sesuai atau tidak ON HOLD.');
            }

            $revision = $this->currentRevision($validation);
            $hash = $this->fileHash($locked, $reference);

            $evidence = Prod05ActivityEvidence::query()->create([
                'validation_id' => $validation->id,
                'activity_control_id' => $locked->id,
                'brief_revision' => $revision,
                'evidence_type' => $type,
                'document_reference' => $reference,
                'description' => $description,
                'file_sha256' => $hash,
                'verification_status' => 'PENDING',
                'submitted_by' => $actor->id,
            ]);

            $this->audit(
                $locked, $evidence, $actor,
                'EVIDENCE_SUBMITTED',
                'ASST_MANAGER_PRODUKSI',
                $description
            );

            return $evidence;
        });
    }

    public function verify(
        Prod05ActivityEvidence $evidence,
        User $actor,
        string $notes
    ): Prod05ActivityEvidence {
        $this->roles->require($actor, 'MANAGER_PRODUKSI');

        $notes = trim($notes);

        if ($notes === '' || mb_strlen($notes) > 5000) {
            $this->reject('Catatan verifikasi wajib dan maksimal 5000 karakter.');
        }

        return DB::transaction(function () use ($evidence, $actor, $notes) {
            $validation = Prod05Validation::query()
                ->whereKey($evidence->validation_id)
                ->lockForUpdate()
                ->firstOrFail();

            $candidate = Prod05ActivityEvidence::query()
                ->whereKey($evidence->id)
                ->firstOrFail();

            $activity = Prod05ActivityControl::query()
                ->whereKey($candidate->activity_control_id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked = Prod05ActivityEvidence::query()
                ->whereKey($evidence->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->validation_id !== (int) $validation->id
                || (int) $activity->validation_id !== (int) $validation->id
                || (int) $locked->activity_control_id !== (int) $activity->id
                || $activity->control_status !== 'ON_HOLD'
                || $locked->verification_status !== 'PENDING'
                || (int) $locked->submitted_by === (int) $actor->id
                || (int) $locked->brief_revision !== $this->currentRevision($validation)) {
                $this->reject('Bukti tidak memenuhi syarat verifikasi.');
            }

            $storedHash = $locked->file_sha256;

            if (! is_string($storedHash)
                || ! preg_match('/\A[a-f0-9]{64}\z/D', $storedHash)
                || ! hash_equals(
                    $storedHash,
                    $this->fileHash($activity, $locked->document_reference)
                )) {
                $this->reject('Checksum bukti tidak cocok atau belum tersedia.');
            }

            $locked->verification_status = 'VERIFIED';
            $locked->verified_by = $actor->id;
            $locked->verified_at = now();
            $locked->verification_notes = $notes;
            $locked->save();

            $this->audit(
                $activity, $locked, $actor,
                'EVIDENCE_VERIFIED',
                'MANAGER_PRODUKSI',
                $notes
            );

            return $locked;
        });
    }
}
