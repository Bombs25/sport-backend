<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test PATCH /api/v1/auth/profile</title>
    <style>
        :root { font-family: system-ui, sans-serif; line-height: 1.5; }
        body { max-width: 40rem; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
        h1 { font-size: 1.125rem; font-weight: 600; }
        p.hint { font-size: 0.875rem; color: #555; margin: 0.5rem 0 1.25rem; }
        label { display: block; font-size: 0.8rem; font-weight: 500; margin-top: 0.75rem; }
        input[type="text"], input[type="number"], input[type="date"], textarea, select {
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
    <h1>Mettre à jour le profil (même requête que l’API)</h1>
    <p class="hint">
        <code>PATCH /api/v1/auth/profile</code> en <code>multipart/form-data</code> (envoyé via <code>POST + _method=PATCH</code>).
        Sanctum stateful :
        <code>/sanctum/csrf-cookie</code> puis <code>X-XSRF-TOKEN</code>. Ne remplis que les champs à envoyer.
        Avatar : fichier image, max ~1&nbsp;Mo (règle API).
    </p>

    <form id="form">
        <label for="bearer">Token Sanctum (sans « Bearer »)</label>
        <input type="text" id="bearer" name="bearer" autocomplete="off" spellcheck="false" autocapitalize="off" placeholder="1|xxxxxxxx…" required>

        <label for="given_name">given_name (optionnel ; avec family_name)</label>
        <input type="text" id="given_name" name="given_name" placeholder="Jean">

        <label for="family_name">family_name (optionnel ; avec given_name)</label>
        <input type="text" id="family_name" name="family_name" placeholder="Dupont">

        <label for="handle">handle (optionnel, 3–32, alphanum + _)</label>
        <input type="text" id="handle" name="handle" placeholder="jean_dupont">

        <label for="birth_date">birth_date (optionnel, avant aujourd’hui)</label>
        <input type="date" id="birth_date" name="birth_date">

        <label for="bio">bio (optionnel)</label>
        <textarea id="bio" name="bio" rows="3" placeholder="…"></textarea>

        <label for="is_private">is_private (optionnel)</label>
        <select id="is_private" name="is_private">
            <option value="" selected>(ne pas envoyer)</option>
            <option value="1">true (privé)</option>
            <option value="0">false (public)</option>
        </select>

        <label for="avatar_url">avatar_url (fichier image, optionnel)</label>
        <input type="file" id="avatar_url" name="avatar_url" accept="image/jpeg,image/png,image/gif,image/webp">

        <label for="latitude">latitude (optionnel)</label>
        <input type="text" id="latitude" name="latitude" placeholder="48.8566">

        <label for="longitude">longitude (optionnel)</label>
        <input type="text" id="longitude" name="longitude" placeholder="2.3522">

        <label for="city">city (optionnel)</label>
        <input type="text" id="city" name="city" placeholder="Paris">

        <label for="address_line">address_line (optionnel)</label>
        <input type="text" id="address_line" name="address_line" placeholder="…">

        <button type="submit">Envoyer PATCH (POST + _method)</button>
    </form>

    <pre id="out">Réponse…</pre>

    <script>
        const form = document.getElementById('form');
        const out = document.getElementById('out');
        const csrfMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        function xsrfTokenFromCookie() {
            const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
            if (! m) return '';
            try { return decodeURIComponent(m[1]); } catch (_) { return m[1]; }
        }

        async function ensureSanctumCsrfCookie() {
            const res = await fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (! res.ok) throw new Error('sanctum/csrf-cookie HTTP ' + res.status);
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
            fd.append('_method', 'PATCH');
            if (! xsrf && csrfMeta) fd.append('_token', csrfMeta);

            const pairs = [
                ['given_name', 'given_name'],
                ['family_name', 'family_name'],
                ['handle', 'handle'],
                ['birth_date', 'birth_date'],
                ['bio', 'bio'],
                ['latitude', 'latitude'],
                ['longitude', 'longitude'],
                ['city', 'city'],
                ['address_line', 'address_line'],
            ];
            for (const [id, key] of pairs) {
                const el = document.getElementById(id);
                const v = el.value.trim();
                if (v) fd.append(key, id === 'bio' ? el.value : v);
            }

            const priv = document.getElementById('is_private').value;
            if (priv !== '') fd.append('is_private', priv);

            const avatar = document.getElementById('avatar_url').files[0];
            if (avatar) fd.append('avatar_url', avatar, avatar.name);

            const url = '/api/v1/auth/profile';
            try {
                const headers = {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                };
                if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
                else if (csrfMeta) headers['X-CSRF-TOKEN'] = csrfMeta;

                const res = await fetch(url, {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    body: fd,
                });
                const text = await res.text();
                let pretty = text;
                try { pretty = JSON.stringify(JSON.parse(text), null, 2); } catch (_) {}
                const statusClass = res.ok ? 'ok' : 'err';
                out.innerHTML = '<span class="' + statusClass + '">HTTP ' + res.status + ' ' + res.statusText + '</span>\n\n' + pretty;
            } catch (err) {
                out.textContent = 'Erreur réseau : ' + err;
            }
        });
    </script>
</body>
</html>
