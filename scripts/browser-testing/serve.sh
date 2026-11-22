#!/bin/sh

set -eu

project_root=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
php_binary="/Users/bayusuwardani/Library/Application Support/Herd/bin/php85"
serve_php="${project_root}/scripts/browser-testing/serve.php"
herd_ini_directory="${HOME}/Library/Application Support/Herd/config/php/85"
upload_ini_directory="${project_root}/scripts/browser-testing/php-ini"
runtime_directory="${project_root}/storage/framework/browser-testing/launcher"
pid_file="${runtime_directory}/server.pid"
log_file="${runtime_directory}/server.log"
lock_directory="${runtime_directory}/control.lock"

if [ ! -x "$php_binary" ] || [ ! -d "$herd_ini_directory" ] || [ ! -f "$upload_ini_directory/99-upload-limits.ini" ]; then
    printf '%s\n' 'Browser testing PHP runtime configuration is incomplete.' >&2
    exit 1
fi

export HERD_PHP_85_INI_SCAN_DIR="${herd_ini_directory}:${upload_ini_directory}"
export PHP_INI_SCAN_DIR="${HERD_PHP_85_INI_SCAN_DIR}"

command_name=${1:-start}
if [ "$#" -gt 0 ]; then
    shift
fi

case "$command_name" in
    --check)
        exec "$php_binary" "$serve_php" --check
        ;;
    --preflight-only|preflight)
        exec "$php_binary" "$serve_php" --preflight-only
        ;;
    check)
        exec "$php_binary" "$serve_php" --check
        ;;
    start|stop|status|cutover-verify|cutover-stop-legacy)
        ;;
    *)
        printf '%s\n' 'Usage: serve.sh {start|stop|status|check|preflight}' >&2
        exit 2
        ;;
esac

if [ "$command_name" = cutover-verify ] || [ "$command_name" = cutover-stop-legacy ]; then
    if [ "$#" -ne 2 ]; then
        printf '%s\n' 'Usage: serve.sh {cutover-verify|cutover-stop-legacy} <artisan-pid> <php-worker-pid>' >&2
        exit 2
    fi
    legacy_artisan_pid=$1
    legacy_worker_pid=$2
elif [ "$#" -gt 0 ]; then
    printf '%s\n' "Unexpected arguments for '${command_name}'." >&2
    exit 2
fi

umask 077

if [ -L "$runtime_directory" ]; then
    printf '%s\n' 'Browser testing launcher runtime directory cannot be a symbolic link.' >&2
    exit 1
fi

mkdir -p "$runtime_directory"
chmod 700 "$runtime_directory"

if [ -L "$pid_file" ] || [ -L "$log_file" ] || [ -L "$lock_directory" ]; then
    printf '%s\n' 'Browser testing launcher state contains a symbolic link.' >&2
    exit 1
fi

if ! mkdir "$lock_directory" 2>/dev/null; then
    printf '%s\n' 'Another browser launcher control operation may be in progress.' >&2
    exit 1
fi
trap 'rmdir "$lock_directory" 2>/dev/null || true' EXIT HUP INT TERM

read_owned_pid() {
    if [ ! -f "$pid_file" ]; then
        return 1
    fi

    value=$(cat "$pid_file")
    case "$value" in
        ''|*[!0-9]*)
            printf '%s\n' 'Launcher PID file is invalid; refusing to signal any process.' >&2
            exit 1
            ;;
    esac
    printf '%s\n' "$value"
}

verify_managed_listener() {
    listener_pids=$(lsof -nP -tiTCP:8082 -sTCP:LISTEN 2>/dev/null || true)
    set -- $listener_pids
    if [ "$#" -ne 1 ]; then
        return 1
    fi

    managed_listener_pid=$1
    listener_command=$(ps -p "$managed_listener_pid" -o command=)
    expected_listener_command="$php_binary -S 127.0.0.1:8082 $project_root/vendor/laravel/framework/src/Illuminate/Foundation/Console/../resources/server.php"
    if [ "$listener_command" != "$expected_listener_command" ]; then
        return 1
    fi

    managed_artisan_pid=$(ps -p "$managed_listener_pid" -o ppid= | tr -d ' ')
    if [ -z "$managed_artisan_pid" ] || [ "$(ps -p "$managed_artisan_pid" -o ppid= | tr -d ' ')" != "$recorded_pid" ]; then
        return 1
    fi

    artisan_command=$(ps -p "$managed_artisan_pid" -o command=)
    expected_artisan_command="$php_binary $project_root/artisan serve --host=127.0.0.1 --port=8082 --tries=1 --no-reload"
    [ "$artisan_command" = "$expected_artisan_command" ]
}

if [ "$command_name" = cutover-verify ] || [ "$command_name" = cutover-stop-legacy ]; then
    case "$legacy_artisan_pid:$legacy_worker_pid" in
        *[!0-9:]*|:*|*:)
            printf '%s\n' 'PIDs must be positive decimal integers.' >&2
            exit 2
            ;;
    esac
    if [ "$legacy_artisan_pid" -le 1 ] || [ "$legacy_worker_pid" -le 1 ]; then
        printf '%s\n' 'PIDs must be positive process identifiers greater than one.' >&2
        exit 2
    fi

    current_uid=$(id -u)
    legacy_listeners=$(lsof -nP -tiTCP:8082 -sTCP:LISTEN 2>/dev/null || true)
    if [ "$legacy_listeners" != "$legacy_worker_pid" ]; then
        printf '%s\n' 'Port 8082 listener identity changed; refusing legacy cutover stop.' >&2
        exit 1
    fi

    legacy_worker_command=$(ps -p "$legacy_worker_pid" -o command=)
    expected_legacy_worker="$php_binary -S 127.0.0.1:8082 $project_root/vendor/laravel/framework/src/Illuminate/Foundation/Console/../resources/server.php"
    if [ "$legacy_worker_command" != "$expected_legacy_worker" ]; then
        printf '%s\n' 'Legacy PHP worker command changed; refusing to signal any process.' >&2
        exit 1
    fi

    if [ "$(ps -p "$legacy_worker_pid" -o uid= | tr -d ' ')" != "$current_uid" ] \
        || [ "$(ps -p "$legacy_artisan_pid" -o uid= | tr -d ' ')" != "$current_uid" ] \
        || [ "$(ps -p "$legacy_worker_pid" -o ppid= | tr -d ' ')" != "$legacy_artisan_pid" ]; then
        printf '%s\n' 'Legacy worker ownership or parent relationship changed; refusing cutover stop.' >&2
        exit 1
    fi

    legacy_wrapper_pid=$(ps -p "$legacy_artisan_pid" -o ppid= | tr -d ' ')
    legacy_artisan_command=$(ps -p "$legacy_artisan_pid" -o command=)
    legacy_wrapper_command=$(ps -p "$legacy_wrapper_pid" -o command=)
    if [ "$legacy_artisan_command" != php ] \
        || ! printf '%s' "$legacy_wrapper_command" | grep -Fq "php <<'PHP'" \
        || ! printf '%s' "$legacy_wrapper_command" | grep -Fq '.enterprise_bpm_browser_db_credentials' \
        || ! printf '%s' "$legacy_wrapper_command" | grep -Fq 'enterprise_bpm_browser_user'; then
        printf '%s\n' 'Legacy wrapper identity did not match the known isolated inline launcher.' >&2
        exit 1
    fi

    if [ "$command_name" = cutover-verify ]; then
        printf 'Legacy wrapper/Artisan/worker identities match (%s -> %s -> %s); no signal sent.\n' "$legacy_wrapper_pid" "$legacy_artisan_pid" "$legacy_worker_pid"
        exit 0
    fi

    kill -TERM "$legacy_artisan_pid"
    attempts=0
    while lsof -nP -tiTCP:8082 -sTCP:LISTEN 2>/dev/null | grep -Fxq "$legacy_worker_pid"; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 30 ]; then
            printf '%s\n' 'Legacy server did not stop gracefully; no force kill was attempted.' >&2
            exit 1
        fi
        sleep 1
    done
    printf 'Verified legacy Artisan PID %s received SIGTERM; port 8082 is clear.\n' "$legacy_artisan_pid"
    exit 0
fi

if [ "$command_name" = status ]; then
    if [ ! -f "$pid_file" ]; then
        printf '%s\n' 'Browser testing launcher is stopped (no owned PID file).'
        exit 0
    fi

    recorded_pid=$(cat "$pid_file")
    case "$recorded_pid" in
        ''|*[!0-9]*)
            printf '%s\n' 'Launcher PID file is invalid; refusing to signal any process.' >&2
            exit 1
            ;;
    esac

    if ! kill -0 "$recorded_pid" 2>/dev/null; then
        printf 'Browser testing launcher PID %s is not running; inspect stale state before removal.\n' "$recorded_pid"
        exit 1
    fi

    recorded_command=$(ps -p "$recorded_pid" -o command=)
    if [ "$recorded_command" != "$php_binary $serve_php" ]; then
        printf 'PID %s no longer matches the managed launcher; refusing to claim ownership.\n' "$recorded_pid" >&2
        exit 1
    fi

    if ! verify_managed_listener; then
        printf '%s\n' 'Launcher process is running; server is starting or has failed before binding.'
        exit 1
    fi

    printf 'Managed listener PID %s is running on port 8082.\n' "$managed_listener_pid"
    exit 0
fi

if [ "$command_name" = stop ]; then
    if ! recorded_pid=$(read_owned_pid); then
        printf '%s\n' 'No launcher-owned server is recorded; no process was signalled.'
        exit 0
    fi

    if ! kill -0 "$recorded_pid" 2>/dev/null; then
        printf 'Recorded launcher PID %s is not running; refusing to infer a replacement process.\n' "$recorded_pid" >&2
        exit 1
    fi

    recorded_command=$(ps -p "$recorded_pid" -o command=)
    case "$recorded_command" in
        "$php_binary $serve_php")
            ;;
        *)
            printf 'PID %s does not match the managed launcher; refusing to stop it.\n' "$recorded_pid" >&2
            exit 1
            ;;
    esac

    if ! verify_managed_listener; then
        printf '%s\n' 'Managed launcher has no port 8082 listener; refusing to signal a possibly unrelated startup process.'
        exit 1
    fi

    kill -TERM "$managed_artisan_pid"
    attempts=0
    while kill -0 "$recorded_pid" 2>/dev/null || [ -n "$(lsof -nP -tiTCP:8082 -sTCP:LISTEN 2>/dev/null || true)" ]; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 30 ]; then
            printf '%s\n' 'Graceful stop timed out; no force kill was attempted.' >&2
            exit 1
        fi
        sleep 1
    done

    if [ "$(cat "$pid_file")" = "$recorded_pid" ]; then
        rm "$pid_file"
    fi
    printf '%s\n' 'Managed browser testing server stopped gracefully.'
    exit 0
fi

if recorded_pid=$(read_owned_pid); then
    if kill -0 "$recorded_pid" 2>/dev/null; then
        recorded_command=$(ps -p "$recorded_pid" -o command=)
        case "$recorded_command" in
            "$php_binary $serve_php")
                printf 'Managed browser server already has launcher PID %s.\n' "$recorded_pid"
                exit 0
                ;;
            *)
                printf 'Recorded PID %s is not the managed launcher; refusing to overwrite its state.\n' "$recorded_pid" >&2
                exit 1
                ;;
        esac
    fi

    printf 'Recorded launcher PID %s is stale; refusing to remove its state automatically.\n' "$recorded_pid" >&2
    exit 1
fi

if [ -n "$(lsof -nP -tiTCP:8082 -sTCP:LISTEN 2>/dev/null || true)" ]; then
    printf '%s\n' '127.0.0.1:8082 is occupied by a process not owned by this launcher; no process was signalled.'
    exit 1
fi

nohup "$php_binary" "$serve_php" >"$log_file" 2>&1 </dev/null &
launcher_pid=$!
printf '%s\n' "$launcher_pid" >"$pid_file"
chmod 600 "$pid_file" "$log_file"

attempts=0
while kill -0 "$launcher_pid" 2>/dev/null; do
    listener_pids=$(lsof -nP -tiTCP:8082 -sTCP:LISTEN 2>/dev/null || true)
    if [ -n "$listener_pids" ]; then
        recorded_pid=$launcher_pid
        if verify_managed_listener; then
            printf 'Browser testing launcher started (PID %s); managed listener PID %s.\n' "$launcher_pid" "$managed_listener_pid"
            exit 0
        fi

        printf '%s\n' 'Port 8082 was bound by a process that failed managed ancestry validation.' >&2
        exit 1
    fi

    attempts=$((attempts + 1))
    if [ "$attempts" -ge 60 ]; then
        printf 'Launcher PID %s has not bound port 8082; inspect %s.\n' "$launcher_pid" "$log_file" >&2
        exit 1
    fi
    sleep 1
done

if [ "$(cat "$pid_file")" = "$launcher_pid" ]; then
    rm "$pid_file"
fi
printf 'Browser launcher exited before binding port 8082; inspect %s.\n' "$log_file" >&2
exit 1
