<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test facturation (checkout / factures / annulation)</title>
    <style>
        :root { font-family: system-ui, sans-serif; line-height: 1.5; }
        body { max-width: 40rem; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
        h1 { font-size: 1.125rem; font-weight: 600; }
        p.hint { font-size: 0.875rem; color: #555; margin: 0.5rem 0 1.25rem; }
        label { display: block; font-size: 0.8rem; font-weight: 500; margin-top: 0.75rem; }
        input[type="text"], input[type="number"] {
            width: 100%; margin-top: 0.25rem; padding: 0.5rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;
        }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.75rem; }
        @media (max-width: 32rem) { .row { grid-template-columns: 1fr; } }
        button {
            margin-top: 1.25rem; margin-right: 0.5rem; padding: 0.6rem 1rem; background: #111; color: #fff; border: 0; border-radius: 6px; cursor: pointer;
        }
        button.secondary { background: #444; }
        button:hover { opacity: 0.9; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        pre {
            margin-top: 1.5rem; padding: 1rem; background: #f4f4f4; border-radius: 6px; overflow: auto; font-size: 0.8rem;
        }
        .ok { color: #0a0; }
        .err { color: #c00; }
        hr { margin: 2.5rem 0; border: 0; border-top: 1px solid #ddd; }
        h2 { font-size: 1rem; font-weight: 600; margin: 0 0 0.5rem; }
        .inline { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem; }
        .inline input { width: auto; margin: 0; }
    </style>
</head>
<body>
    <h1>Créer une session Stripe Checkout (abonnement)</h1>
    <p class="hint">
        <code>POST /api/v1/auth/billing/checkout</code> — Sanctum stateful :
        <code>/sanctum/csrf-cookie</code> puis <code>X-XSRF-TOKEN</code>.
        Vérifie <code>STRIPE_SUBSCRIPTION_PRICE_ID</code>, <code>BILLING_SUBSCRIPTION_TRIAL_DAYS</code> et les URLs dans <code>.env</code>.
        En local : <code>stripe listen --forward-to {{ url('/stripe/webhook') }}</code>.
    </p>

    <form id="form">
        <label for="bearer">Token Sanctum (sans « Bearer »)</label>
        <input type="text" id="bearer" name="bearer" autocomplete="off" spellcheck="false" autocapitalize="off" placeholder="1|xxxxxxxx…" required>

        <button type="submit">POST checkout</button>
        <button type="button" id="openCheckout" class="secondary" disabled>Ouvrir Stripe (dernière URL)</button>
    </form>

    <pre id="out">Réponse…</pre>

    <hr>

    <h2>Annuler l’abonnement</h2>
    <p class="hint">
        <code>POST /api/v1/auth/billing/subscription/cancel</code> — par défaut annulation à la <strong>fin de période</strong> (accès conservé jusqu’à cette date).
        Coche « immédiat » pour couper tout de suite (<code>cancelNow</code> côté Cashier).
    </p>
    <form id="cancelForm">
        <label for="bearerCancel">Token Sanctum (sans « Bearer »)</label>
        <input type="text" id="bearerCancel" name="bearerCancel" autocomplete="off" spellcheck="false" autocapitalize="off" placeholder="1|xxxxxxxx…" required>
        <div class="inline">
            <input type="checkbox" id="immediately" name="immediately">
            <label for="immediately" style="margin:0;font-weight:400;">Annulation immédiate (<code>immediately: true</code>)</label>
        </div>
        <button type="submit">POST annulation</button>
    </form>

    <pre id="outCancel">Réponse annulation…</pre>

    <hr>

    <h2>Liste des factures</h2>
    <p class="hint">
        <code>GET /api/v1/auth/billing/invoices</code> — query optionnelles <code>month</code> + <code>year</code> (filtrer sur le mois, fuseau <code>APP_TIMEZONE</code>), <code>limit</code> (1–100, défaut 24).
        Utilise <code>hosted_invoice_url</code> ou <code>invoice_pdf</code> comme lien « Voir ».
    </p>
    <form id="invoicesForm">
        <label for="bearerInvoices">Token Sanctum (sans « Bearer »)</label>
        <input type="text" id="bearerInvoices" name="bearerInvoices" autocomplete="off" spellcheck="false" autocapitalize="off" placeholder="1|xxxxxxxx…" required>
        <div class="row">
            <div>
                <label for="invMonth">month (1–12, avec year)</label>
                <input type="number" id="invMonth" name="invMonth" min="1" max="12" placeholder="5">
            </div>
            <div>
                <label for="invYear">year (avec month)</label>
                <input type="number" id="invYear" name="invYear" min="2000" max="2100" placeholder="2026">
            </div>
        </div>
        <label for="invLimit">limit (optionnel)</label>
        <input type="number" id="invLimit" name="invLimit" min="1" max="100" placeholder="24">
        <button type="submit">GET factures</button>
    </form>

    <pre id="outInvoices">Réponse factures…</pre>

    <script>
        const form = document.getElementById('form');
        const cancelForm = document.getElementById('cancelForm');
        const invoicesForm = document.getElementById('invoicesForm');
        const out = document.getElementById('out');
        const outCancel = document.getElementById('outCancel');
        const outInvoices = document.getElementById('outInvoices');
        const openBtn = document.getElementById('openCheckout');
        const csrfMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        let lastCheckoutUrl = '';

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

        function apiHeaders(token, xsrf) {
            const headers = {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };
            if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
            else if (csrfMeta) headers['X-CSRF-TOKEN'] = csrfMeta;
            return headers;
        }

        async function showJsonResponse(res, preEl) {
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (_) { data = null; }
            const pretty = data ? JSON.stringify(data, null, 2) : text;
            const statusClass = res.ok ? 'ok' : 'err';
            preEl.innerHTML = '<span class="' + statusClass + '">HTTP ' + res.status + ' ' + res.statusText + '</span>\n\n' + pretty;
            return { res, data };
        }

        openBtn.addEventListener('click', () => {
            if (lastCheckoutUrl) window.open(lastCheckoutUrl, '_blank', 'noopener,noreferrer');
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            out.textContent = 'Envoi en cours…';
            openBtn.disabled = true;
            lastCheckoutUrl = '';

            try {
                await ensureSanctumCsrfCookie();
            } catch (err) {
                out.textContent = 'CSRF : ' + err;
                return;
            }

            const xsrf = xsrfTokenFromCookie();
            const token = document.getElementById('bearer').value.trim();
            const url = '/api/v1/auth/billing/checkout';

            try {
                const { res, data } = await fetch(url, {
                    method: 'POST',
                    headers: apiHeaders(token, xsrf),
                    credentials: 'same-origin',
                    body: '{}',
                }).then(r => showJsonResponse(r, out));

                if (res.ok && data && typeof data.checkout_url === 'string') {
                    lastCheckoutUrl = data.checkout_url;
                    openBtn.disabled = false;
                }
            } catch (err) {
                out.textContent = 'Erreur réseau : ' + err;
            }
        });

        cancelForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            outCancel.textContent = 'Envoi en cours…';

            try {
                await ensureSanctumCsrfCookie();
            } catch (err) {
                outCancel.textContent = 'CSRF : ' + err;
                return;
            }

            const xsrf = xsrfTokenFromCookie();
            const token = document.getElementById('bearerCancel').value.trim();
            const immediately = document.getElementById('immediately').checked;
            const url = '/api/v1/auth/billing/subscription/cancel';

            try {
                await fetch(url, {
                    method: 'POST',
                    headers: apiHeaders(token, xsrf),
                    credentials: 'same-origin',
                    body: JSON.stringify({ immediately }),
                }).then(r => showJsonResponse(r, outCancel));
            } catch (err) {
                outCancel.textContent = 'Erreur réseau : ' + err;
            }
        });

        invoicesForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            outInvoices.textContent = 'Chargement…';

            try {
                await ensureSanctumCsrfCookie();
            } catch (err) {
                outInvoices.textContent = 'CSRF : ' + err;
                return;
            }

            const xsrf = xsrfTokenFromCookie();
            const token = document.getElementById('bearerInvoices').value.trim();
            const params = new URLSearchParams();
            const month = document.getElementById('invMonth').value.trim();
            const year = document.getElementById('invYear').value.trim();
            if (month !== '' && year !== '') {
                params.set('month', month);
                params.set('year', year);
            }
            const limit = document.getElementById('invLimit').value.trim();
            if (limit !== '') params.set('limit', limit);
            const qs = params.toString();
            const url = '/api/v1/auth/billing/invoices' + (qs ? '?' + qs : '');

            try {
                const hdrs = apiHeaders(token, xsrf);
                delete hdrs['Content-Type'];
                await fetch(url, {
                    method: 'GET',
                    headers: hdrs,
                    credentials: 'same-origin',
                }).then(r => showJsonResponse(r, outInvoices));
            } catch (err) {
                outInvoices.textContent = 'Erreur réseau : ' + err;
            }
        });
    </script>
</body>
</html>
