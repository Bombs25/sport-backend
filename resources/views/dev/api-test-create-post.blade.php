<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test POST /api/v1/auth/posts</title>
    <style>
        :root { font-family: system-ui, sans-serif; line-height: 1.5; }
        body { max-width: 40rem; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
        h1 { font-size: 1.125rem; font-weight: 600; }
        p.hint { font-size: 0.875rem; color: #555; margin: 0.5rem 0 1.25rem; }
        label { display: block; font-size: 0.8rem; font-weight: 500; margin-top: 0.75rem; }
        input[type="text"], textarea, select {
            width: 100%; margin-top: 0.25rem; padding: 0.5rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;
        }
        input[type="file"] { margin-top: 0.25rem; }
        button {
            margin-top: 1.25rem; padding: 0.6rem 1rem; background: #111; color: #fff; border: 0; border-radius: 6px; cursor: pointer;
        }
        button:hover { background: #333; }
        pre {
            margin-top: 1.5rem; padding: 1rem; background: #f4f4f4; border-radius: 6px; overflow: auto; font-size: 0.8rem;
        }
        .ok { color: #0a0; }
        .err { color: #c00; }
    </style>
</head>
<body>
    <h1>Créer un post régulier (même requête que l’API)</h1>
    <p class="hint">
        Envoie un <code>multipart/form-data</code> vers <code>/api/v1/auth/posts</code>.
        Les fichiers sont envoyés dans <code>media[]</code> et passent ensuite par <code>ImageProcessingEvent</code>.
        Après la réponse <code>201</code>, les lignes <code>post_media</code> seront remplies par la queue <code>image-processing</code>.
    </p>

    <form id="form">
        <label for="bearer">Token Sanctum (sans le préfixe « Bearer »)</label>
        <input type="text" id="bearer" name="bearer" autocomplete="off" spellcheck="false" autocapitalize="off" placeholder="1|xxxxxxxx…" required>

        <label for="body">body</label>
        <textarea id="body" name="body" rows="4" placeholder="Great win today against the Thunderbolts!">Great win today against the Thunderbolts! 3-1 victory. Hard work pays off!</textarea>

        <label for="visibility">visibility</label>
        <select id="visibility" name="visibility">
            <option value="public" selected>public</option>
            <option value="followers">followers</option>
        </select>

        <label for="media">media[] (max 3 images, 5 MB chacune)</label>
        <input type="file" id="media" name="media[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>

        <button type="submit">Envoyer POST</button>
    </form>

    <pre id="out">Réponse…</pre>

    <script>
        const form = document.getElementById('form');
        const out = document.getElementById('out');
        const csrfMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        function xsrfTokenFromCookie() {
            const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
            if (! m) {
                return '';
            }
            try {
                return decodeURIComponent(m[1]);
            } catch (_) {
                return m[1];
            }
        }

        async function ensureSanctumCsrfCookie() {
            const res = await fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (! res.ok) {
                throw new Error('sanctum/csrf-cookie HTTP ' + res.status);
            }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            out.textContent = 'Envoi en cours…';

            try {
                await ensureSanctumCsrfCookie();
            } catch (err) {
                out.textContent = 'CSRF : ' + err;
                return;
            }

            const xsrf = xsrfTokenFromCookie();
            const token = document.getElementById('bearer').value.trim();
            const fd = new FormData();

            if (! xsrf && csrfMeta) {
                fd.append('_token', csrfMeta);
            }

            const body = document.getElementById('body').value;
            if (body.trim() !== '') {
                fd.append('body', body);
            }

            const visibility = document.getElementById('visibility').value;
            if (visibility) {
                fd.append('visibility', visibility);
            }

            const files = Array.from(document.getElementById('media').files).slice(0, 3);
            for (const file of files) {
                fd.append('media[]', file, file.name);
            }

            try {
                const headers = {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                };
                if (xsrf) {
                    headers['X-XSRF-TOKEN'] = xsrf;
                } else if (csrfMeta) {
                    headers['X-CSRF-TOKEN'] = csrfMeta;
                }

                const res = await fetch('/api/v1/auth/posts', {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    body: fd,
                });

                const text = await res.text();
                let pretty = text;
                try {
                    pretty = JSON.stringify(JSON.parse(text), null, 2);
                } catch (_) {}
                const statusClass = res.ok ? 'ok' : 'err';
                out.innerHTML = '<span class="' + statusClass + '">HTTP ' + res.status + ' ' + res.statusText + '</span>\n\n' + pretty;
            } catch (err) {
                out.textContent = 'Erreur réseau : ' + err;
            }
        });
    </script>
</body>
</html>
