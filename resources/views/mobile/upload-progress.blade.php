<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Envoi en cours</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --accent: #2563eb;
            --track: #e2e8f0;
            --ok: #16a34a;
            --err: #dc2626;
        }
        [data-theme="dark"] {
            --bg: #0f172a;
            --card: #1e293b;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --accent: #3b82f6;
            --track: #334155;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .card {
            width: 100%;
            max-width: 22rem;
            background: var(--card);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08);
        }
        h1 { font-size: 1.125rem; font-weight: 600; margin: 0 0 0.25rem; }
        .status { font-size: 0.875rem; color: var(--muted); margin: 0 0 1.25rem; min-height: 1.25rem; }
        .bar-wrap {
            height: 0.5rem;
            background: var(--track);
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .bar-fill {
            height: 100%;
            width: 0%;
            background: var(--accent);
            border-radius: 999px;
            transition: width 0.35s ease;
        }
        .percent { font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .mono {
            margin-top: 0.75rem;
            font-size: 0.7rem;
            color: var(--muted);
            font-family: ui-monospace, monospace;
            word-break: break-all;
            line-height: 1.4;
        }
        .error { color: var(--err); }
        .done { color: var(--ok); }
    </style>
</head>
<body data-theme="{{ request()->query('theme', 'light') }}">
    <div class="card" id="card">
        <h1>Envoi des fichiers</h1>
        <p class="status" id="status">Connexion…</p>
        <div class="bar-wrap"><div class="bar-fill" id="bar"></div></div>
        <p class="percent" id="percent">0%</p>
        <p class="mono" id="detail" hidden></p>
    </div>

    @php
        $reverbClientConfig = [
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => (int) config('broadcasting.connections.reverb.options.port', 8080),
            'scheme' => config('broadcasting.connections.reverb.options.scheme', 'https'),
        ];
    @endphp

    <script src="https://js.pusher.com/8.4.0-rc2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.19.0/dist/echo.iife.js"></script>
    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            const token = (params.get('token') || '').trim();
            const debug = params.get('debug') === '1';
            const theme = params.get('theme');
            if (theme === 'dark' || theme === 'light') {
                document.body.setAttribute('data-theme', theme);
            }

            const statusEl = document.getElementById('status');
            const barEl = document.getElementById('bar');
            const percentEl = document.getElementById('percent');
            const detailEl = document.getElementById('detail');

            const reverb = @json($reverbClientConfig);

            function notifyHost(payload) {
                const msg = JSON.stringify(payload);
                if (window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage === 'function') {
                    window.ReactNativeWebView.postMessage(msg);
                }
                if (window.parent !== window) {
                    window.parent.postMessage(msg, '*');
                }
            }

            function setProgress(percent, label, detail, cssClass) {
                const p = Math.min(100, Math.max(0, Number(percent) || 0));
                barEl.style.width = p + '%';
                percentEl.textContent = p + '%';
                statusEl.textContent = label;
                statusEl.className = 'status' + (cssClass ? ' ' + cssClass : '');
                if (detail) {
                    detailEl.hidden = false;
                    detailEl.textContent = detail;
                }
            }

            if (! token) {
                setProgress(0, 'Token manquant (?token=…)', null, 'error');
                notifyHost({ type: 'upload.error', message: 'missing_token' });
                return;
            }

            if (! reverb.key || ! reverb.host) {
                setProgress(0, 'Reverb non configuré côté serveur', null, 'error');
                notifyHost({ type: 'upload.error', message: 'reverb_not_configured' });
                return;
            }

            const apiBase = window.location.origin + '/api/v1/auth';
            const broadcastAuth = window.location.origin + '/api/broadcasting/auth';
            const useTls = (reverb.scheme || 'https') === 'https';
            const wsPort = Number(reverb.port) || (useTls ? 443 : 80);
            const wssPort = Number(reverb.port) || 443;
            // localhost en .env mais page ouverte en 127.0.0.1 → WebSocket doit cibler le même hôte.
            const wsHost = (reverb.host === 'localhost' || reverb.host === '127.0.0.1')
                ? window.location.hostname
                : reverb.host;

            function logDebug() {
                if (debug && console && console.log) {
                    console.log.apply(console, arguments);
                }
            }

            /** @param {unknown} raw */
            function normalizeUploadEvent(raw) {
                if (raw == null) {
                    return {};
                }
                if (typeof raw === 'string') {
                    try {
                        return JSON.parse(raw);
                    } catch (_) {
                        return {};
                    }
                }
                if (typeof raw === 'object' && raw !== null) {
                    const obj = /** @type {Record<string, unknown>} */ (raw);
                    if (typeof obj.data === 'string') {
                        try {
                            return { ...JSON.parse(obj.data), ...obj };
                        } catch (_) {
                            return obj;
                        }
                    }
                    return obj;
                }
                return {};
            }

            let pollTimer = null;
            let lastPercent = -1;

            function stopPolling() {
                if (pollTimer !== null) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            function handleUploadEvent(raw) {
                const e = normalizeUploadEvent(raw);
                const percent = Number(e.percent ?? 0);
                const jobs = (e.processed_jobs != null && e.total_jobs != null)
                    ? e.processed_jobs + ' / ' + e.total_jobs + ' étapes'
                    : '';
                const detail = (e.progress_bar || jobs || '').toString();

                if (percent === lastPercent && e.status !== 'completed' && e.status !== 'failed') {
                    return;
                }
                lastPercent = percent;

                logDebug('file.upload.progress', e);

                if (e.status === 'completed') {
                    stopPolling();
                    setProgress(100, 'Terminé', detail, 'done');
                    notifyHost(Object.assign({ type: 'upload.complete' }, e));
                    return;
                }
                if (e.status === 'failed') {
                    stopPolling();
                    setProgress(percent, 'Échec du traitement', detail, 'error');
                    notifyHost(Object.assign({ type: 'upload.failed' }, e));
                    return;
                }

                setProgress(percent, 'Traitement en cours…', detail);
                notifyHost(Object.assign({ type: 'upload.progress' }, e));
            }

            function startPolling(batchKey) {
                stopPolling();
                const pollUrl = apiBase + '/upload-progress' + (batchKey ? ('?batch_key=' + encodeURIComponent(batchKey)) : '');

                pollTimer = setInterval(function () {
                    fetch(pollUrl, {
                        method: 'GET',
                        credentials: 'omit',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json',
                        },
                    })
                        .then(function (res) {
                            if (res.status === 404) {
                                stopPolling();
                                logDebug('upload-progress poll disabled (route 404 — run php artisan route:clear)');
                                return null;
                            }
                            return res.ok ? res.json() : null;
                        })
                        .then(function (body) {
                            if (body && body.data) {
                                handleUploadEvent(body.data);
                            }
                        })
                        .catch(function () {});
                }, 400);
            }

            function bindPusherChannelEvents(pusherChannel) {
                if (! pusherChannel) {
                    return;
                }
                pusherChannel.bind('file.upload.progress', handleUploadEvent);
            }

            async function fetchCurrentUser() {
                const res = await fetch(apiBase + '/user', {
                    method: 'GET',
                    credentials: 'omit',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                    },
                });
                if (! res.ok) {
                    throw new Error('user HTTP ' + res.status);
                }
                const data = await res.json();
                return data.user;
            }

            function subscribe(userId) {
                const batchKey = (params.get('batch_key') || '').trim();
                const channelName = 'file.upload.progress.' + userId;
                const pusherChannelName = 'private-' + channelName;
                const authHeaders = {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                };

                function authorizeChannel(socketId, channelNameFull, callback) {
                    fetch(broadcastAuth, {
                        method: 'POST',
                        credentials: 'omit',
                        headers: authHeaders,
                        body: JSON.stringify({
                            socket_id: socketId,
                            channel_name: channelNameFull,
                        }),
                    })
                        .then(function (response) {
                            if (! response.ok) {
                                logDebug('broadcasting/auth HTTP', response.status);
                                callback(new Error('broadcasting auth ' + response.status), null);
                                return null;
                            }
                            return response.json();
                        })
                        .then(function (data) {
                            if (data === null) {
                                return;
                            }
                            callback(null, data);
                        })
                        .catch(function (err) {
                            callback(err, null);
                        });
                }

                window.Pusher = Pusher;
                window.Echo = new Echo({
                    broadcaster: 'reverb',
                    key: reverb.key,
                    wsHost: wsHost,
                    wsPort: wsPort,
                    wssPort: wssPort,
                    forceTLS: useTls,
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                    authorizer: function (channel) {
                        return {
                            authorize: function (socketId, callback) {
                                authorizeChannel(socketId, channel.name, function (error, data) {
                                    if (error) {
                                        callback(error, data);
                                        return;
                                    }
                                    callback(null, data);
                                });
                            },
                        };
                    },
                });

                const pusher = window.Echo.connector.pusher;
                pusher.connection.bind('state_change', function (states) {
                    logDebug('pusher state', states.previous, '→', states.current);
                    if (states.current === 'connected') {
                        setProgress(0, 'WebSocket connecté — en attente…', null);
                    }
                    if (states.current === 'failed' || states.current === 'unavailable') {
                        setProgress(0, 'WebSocket indisponible (Reverb démarré ?)', null, 'error');
                        notifyHost({ type: 'upload.error', message: 'websocket_failed' });
                    }
                });

                const channel = window.Echo.private(channelName);

                channel.error(function (error) {
                    logDebug('channel error', error);
                    setProgress(0, 'Impossible de rejoindre le canal privé', null, 'error');
                    notifyHost({ type: 'upload.error', message: 'channel_error', error: error });
                });

                channel.subscribed(function () {
                    logDebug('echo subscribed', channelName);
                    setProgress(0, 'Écoute active — lancez l’upload', null);
                    notifyHost({ type: 'upload.ready', user_id: userId, channel: channelName });

                    bindPusherChannelEvents(pusher.channel(pusherChannelName));
                    startPolling(batchKey);
                });

                channel.listen('.file.upload.progress', handleUploadEvent);
                channel.listen('file.upload.progress', handleUploadEvent);

                setProgress(0, 'Connexion au canal…', null);
            }

            fetchCurrentUser()
                .then(function (user) {
                    if (! user || ! user.id) {
                        throw new Error('invalid user');
                    }
                    const batchKey = (params.get('batch_key') || '').trim();
                    startPolling(batchKey);
                    subscribe(user.id);
                })
                .catch(function (err) {
                    setProgress(0, 'Session invalide ou expirée', String(err.message || err), 'error');
                    notifyHost({ type: 'upload.error', message: 'auth_failed' });
                });
        })();
    </script>
</body>
</html>
