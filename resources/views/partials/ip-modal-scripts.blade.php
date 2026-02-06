<script>
    (function () {
        // Config
        const enabled = @json((bool) config('traffic-sentinel.ui.ip_modal.enabled', true));
        if (!enabled) return;

        window.TS = window.TS || {};
        TS.routes = TS.routes || {};

        // endpoint supports __IP__ placeholder
        TS.routes.ipLookup = @json(config('traffic-sentinel.ui.ip_modal.endpoint', '/admin/traffic-sentinel/ip/lookup?ip=__IP__'));

        const modalEl = document.getElementById('tsIpModal');
        if (!modalEl || !window.bootstrap) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        const $loading = document.getElementById('tsIpModalLoading');
        const $body    = document.getElementById('tsIpModalBody');
        const $error   = document.getElementById('tsIpModalError');

        const $flag    = document.getElementById('tsIpModalFlag');
        const $title   = document.getElementById('tsIpModalTitle');

        const $ip      = document.getElementById('tsIpModalIp');
        const $country = document.getElementById('tsIpModalCountry');
        const $cc      = document.getElementById('tsIpModalCountryCode');

        const $asn     = document.getElementById('tsIpModalAsn');
        const $asnChip = document.getElementById('tsIpModalAsnChip');
        const $asnCC   = document.getElementById('tsIpModalAsnCountry');

        const $cidr    = document.getElementById('tsIpModalCidr');

        const $countryChip = document.getElementById('tsIpModalCountryChip');

        const $copyBtn = document.getElementById('tsCopyIpBtn');
        const $copyOk  = document.getElementById('tsCopyOk');

        const $whois   = document.getElementById('tsOpenWhoisLink');
        const $openIp  = document.getElementById('tsOpenIpLink');

        TS.ipCache = TS.ipCache || {};

        function setState({loading=false, error=null}){
            $error.classList.toggle('d-none', !error);
            if (error) $error.textContent = error;

            $loading.style.display = loading ? '' : 'none';
            $body.classList.toggle('d-none', loading || !!error);
        }

        function safe(v, fallback='—'){
            return (v === null || v === undefined || v === '') ? fallback : v;
        }

        function lookupUrl(ip){
            if (TS.routes && TS.routes.ipLookup) {
                return TS.routes.ipLookup.replace('__IP__', encodeURIComponent(ip));
            }
            return '/admin/traffic-sentinel/ip/lookup?ip=' + encodeURIComponent(ip);
        }

        async function fetchIp(ip){
            if (TS.ipCache[ip]) return TS.ipCache[ip];

            const res = await fetch(lookupUrl(ip), { headers: { 'Accept': 'application/json' }});
            if (!res.ok) throw new Error('Request failed (' + res.status + ')');

            const json = await res.json();
            const d = json.data ?? json;
            TS.ipCache[ip] = d;
            return d;
        }

        // Modal open on click
        document.addEventListener('click', async function (e) {
            const el = e.target.closest('[data-ts-ip]');
            if (!el) return;

            e.preventDefault();

            const ipVal = el.getAttribute('data-ts-ip');
            if (!ipVal) return;

            // reset UI
            $flag.textContent = '🌐';
            $title.textContent = ipVal;
            $ip.textContent = ipVal;

            $country.textContent = '—';
            $cc.textContent = '';
            $asn.textContent = '—';
            $asnCC.textContent = '';
            $cidr.textContent = '—';

            $countryChip.textContent = '—';
            $asnChip.textContent = '—';

            $copyOk.classList.add('d-none');

            $whois.href = 'https://whois.domaintools.com/' + encodeURIComponent(ipVal);
            $openIp.href = 'https://ipinfo.io/' + encodeURIComponent(ipVal);

            setState({loading:true, error:null});
            modal.show();

            try{
                const d = await fetchIp(ipVal);

                $flag.textContent = safe(d.flag, '🌐');

                const countryName = safe(d.country_name, 'Unknown');
                const countryCode = safe(d.country_code, null);

                $country.textContent = countryName;
                $cc.textContent = countryCode ? ('(' + countryCode + ')') : '';
                $countryChip.textContent = countryCode ? (countryCode + ' — ' + countryName) : countryName;

                const asnNum  = safe(d.asn, null);
                const asnName = safe(d.asn_name, null);
                const asnCCv  = safe(d.asn_country_code, null);

                if (asnNum) {
                    $asn.textContent = 'AS' + asnNum + (asnName ? (' — ' + asnName) : '');
                    $asnChip.textContent = 'AS' + asnNum;
                } else {
                    $asn.textContent = '—';
                    $asnChip.textContent = '—';
                }

                $asnCC.textContent = asnCCv ? ('Country: ' + asnCCv) : '';
                $cidr.textContent = safe(d.cidr, '—');

                setState({loading:false, error:null});
            }catch(err){
                setState({loading:false, error: err?.message || 'Failed to load IP details'});
            }
        });

        // Copy
        $copyBtn?.addEventListener('click', async function(){
            const val = $ip.textContent || '';
            try{
                await navigator.clipboard.writeText(val);
            }catch(e){
                const ta = document.createElement('textarea');
                ta.value = val;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            $copyOk.classList.remove('d-none');
            setTimeout(()=> $copyOk.classList.add('d-none'), 1400);
        });

        // Flag hydration (inline flags in tables)
        TS.hydrateIpFlags = async function(scope=document){
            const hydrateEnabled = @json((bool) config('traffic-sentinel.ui.ip_modal.hydrate_flags', true));
            if (!hydrateEnabled) return;

            const els = scope.querySelectorAll('[data-ts-flag]');
            const ips = [...new Set([...els].map(el => el.getAttribute('data-ts-flag')).filter(Boolean))];

            for (const ip of ips) {
                if (TS.ipCache[ip]) continue;
                try { await fetchIp(ip); } catch(e) {}
            }

            els.forEach(el => {
                const ip = el.getAttribute('data-ts-flag');
                const d = TS.ipCache[ip];
                if (d && d.flag) el.textContent = d.flag;
            });
        };

        document.addEventListener('DOMContentLoaded', () => TS.hydrateIpFlags());
        document.addEventListener('ts:datatable:draw', () => TS.hydrateIpFlags());
    })();
</script>
