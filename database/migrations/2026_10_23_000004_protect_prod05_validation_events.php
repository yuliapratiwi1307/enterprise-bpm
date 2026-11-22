<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('PROD-05 audit guard requires PostgreSQL.');
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION prod05_reject_validation_event_mutation()
RETURNS trigger
LANGUAGE plpgsql
AS $body$
BEGIN
    RAISE EXCEPTION 'prod05_validation_events is append-only: % denied', TG_OP;
END;
$body$;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER prod05_validation_events_append_only
BEFORE UPDATE OR DELETE ON prod05_validation_events
FOR EACH ROW EXECUTE FUNCTION prod05_reject_validation_event_mutation();
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prod05_validation_events_append_only ON prod05_validation_events');
        DB::unprepared('DROP FUNCTION IF EXISTS prod05_reject_validation_event_mutation()');
    }
};
