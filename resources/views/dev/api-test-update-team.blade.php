<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test PATCH /api/v1/auth/teams/{team_id}</title>
    <style>
        :root { font-family: system-ui, sans-serif; line-height: 1.5; }
        body { max-width: 40rem; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
        h1 { font-size: 1.125rem; font-weight: 600; }
        p.hint { font-size: 0.875rem; color: #555; margin: 0.5rem 0 1.25rem; }
        label { display: block; font-size: 0.8rem; font-weight: 500; margin-top: 0.75rem; }
        input[type="text"], input[type="number"], input[type="password"], textarea, select {
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
    <h1>Mettre à jour une équipe (même requête que l’API)</h1>
    <p class="hint">
        Envoie un <code>multipart/form-data</code> en <code>PATCH</code> vers <code>/api/v1/auth/teams/{team_id}</code>
        via <code>POST + _method=PATCH</code> (même origine).
        Sanctum stateful : <code>/sanctum/csrf-cookie</code> puis <code>X-XSRF-TOKEN</code>, comme la page « create-team ».
        Champs texte optionnels ; fichiers optionnels (pipeline image si présents).
    </p>

    <form id="form">
        <label for="bearer">Token Sanctum (sans le préfixe « Bearer »)</label>
        <input type="text" id="bearer" name="bearer" autocomplete="off" spellcheck="false" autocapitalize="off" placeholder="1|xxxxxxxx…" required>

        <label for="team_id">team_id (dans l’URL)</label>
        <input type="number" id="team_id" name="team_id" value="1" min="1" required>

        <label for="name">name (optionnel)</label>
        <input type="text" id="name" name="name" placeholder="Laisser vide pour ne pas envoyer">

        <label for="sport_id">sport_id (optionnel)</label>
        <input type="number" id="sport_id" name="sport_id" placeholder="ex. 1">

        <label for="description">description (optionnel)</label>
        <textarea id="description" name="description" rows="2" placeholder="Laisser vide pour ne pas envoyer"></textarea>

        <label for="hq_city">hq_city (optionnel)</label>
        <input type="text" id="hq_city" name="hq_city" placeholder="Paris">

        <label for="hq_latitude">hq_latitude (optionnel)</label>
        <input type="text" id="hq_latitude" name="hq_latitude" placeholder="48.8566">

        <label for="hq_longitude">hq_longitude (optionnel)</label>
        <input type="text" id="hq_longitude" name="hq_longitude" placeholder="2.3522">

        <label for="cover_image_url">cover_image_url (fichier, optionnel)</label>
        <input type="file" id="cover_image_url" name="cover_image_url" accept="image/jpeg,image/png,image/gif,image/webp">

        <label for="logo_url">logo_url (fichier, optionnel)</label>
        <input type="file" id="logo_url" name="logo_url" accept="image/jpeg,image/png,image/gif,image/webp">

        <label for="competition_type">competition_type (optionnel)</label>
        <select id="competition_type" name="competition_type">
            <option value="" selected>(ne pas envoyer)</option>
            <option value="leisure">leisure</option>
            <option value="competitive">competitive</option>
        </select>

        <label for="skill_level">skill_level (optionnel)</label>
        <select id="skill_level" name="skill_level">
            <option value="" selected>(ne pas envoyer)</option>
            <option value="beginner">beginner</option>
            <option value="intermediate">intermediate</option>
            <option value="expert">expert</option>
        </select>

        <button type="submit">Envoyer PATCH (POST + _method)</button>
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
            const teamId = document.getElementById('team_id').value.trim();
            if (! teamId) {
                out.textContent = 'team_id requis.';
                return;
            }

            const fd = new FormData();
            fd.append('_method', 'PATCH');
            if (! xsrf && csrfMeta) {
                fd.append('_token', csrfMeta);
            }

            const name = document.getElementById('name').value.trim();
            if (name) fd.append('name', name);

            const sportId = document.getElementById('sport_id').value.trim();
            if (sportId) fd.append('sport_id', sportId);

            const desc = document.getElementById('description').value;
            if (desc.trim()) fd.append('description', desc);

            const city = document.getElementById('hq_city').value.trim();
            if (city) fd.append('hq_city', city);

            const lat = document.getElementById('hq_latitude').value.trim();
            if (lat) fd.append('hq_latitude', lat);

            const lng = document.getElementById('hq_longitude').value.trim();
            if (lng) fd.append('hq_longitude', lng);

            const cover = document.getElementById('cover_image_url').files[0];
            if (cover) fd.append('cover_image_url', cover, cover.name);

            const logo = document.getElementById('logo_url').files[0];
            if (logo) fd.append('logo_url', logo, logo.name);

            const ct = document.getElementById('competition_type').value;
            if (ct) fd.append('competition_type', ct);

            const sl = document.getElementById('skill_level').value;
            if (sl) fd.append('skill_level', sl);

            const url = '/api/v1/auth/teams/' + encodeURIComponent(teamId);
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

                const res = await fetch(url, {
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
