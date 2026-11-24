(function () {
    if (window.FormsCaptcha) {
        window.FormsCaptcha.render();
        return;
    }

    // How each provider is loaded, rendered and reset. Turnstile, hCaptcha and reCAPTCHA v2 are
    // rendered explicitly, so each widget's id is known and a failed submit can reset just that one.
    var providers = {
        turnstile: {
            global: 'turnstile',
            src: function () {
                return 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
            },
            render: function (api, el) {
                return api.render(el, options(el));
            },
            reset: function (api, id) {
                api.reset(id);
            },
        },
        hcaptcha: {
            global: 'hcaptcha',
            src: function () {
                return 'https://js.hcaptcha.com/1/api.js?render=explicit';
            },
            render: function (api, el) {
                return api.render(el, options(el));
            },
            reset: function (api, id) {
                api.reset(id);
            },
        },
        recaptcha: {
            global: 'grecaptcha',
            src: function () {
                return 'https://www.google.com/recaptcha/api.js?render=explicit';
            },
            render: function (api, el) {
                return api.render(el, options(el));
            },
            reset: function (api, id) {
                api.reset(id);
            },
        },
        recaptcha_v3: {
            global: 'grecaptcha',
            ready: true,
            src: function (el) {
                return 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(el.dataset.sitekey);
            },
            render: function () {
                return 'v3';
            },
            reset: function (api, id, el) {
                input(el).value = '';
            },
        },
    };

    var loading = {};
    var waiting = {};
    var nonce = document.currentScript && document.currentScript.nonce;

    // Only these providers, from these URLs: the page's markup can't point the loader anywhere else.
    function provider(el) {
        var kind = el.dataset.formsCaptcha;
        return Object.prototype.hasOwnProperty.call(providers, kind) ? providers[kind] : null;
    }

    function options(el) {
        var o = { sitekey: el.dataset.sitekey };
        if (el.dataset.theme) o.theme = el.dataset.theme;
        if (el.dataset.size) o.size = el.dataset.size;
        return o;
    }

    function input(el) {
        return el.querySelector('input[name="g-recaptcha-response"]');
    }

    // Loads a provider's script once, then calls back for every widget waiting on it.
    function load(el, callback) {
        var kind = el.dataset.formsCaptcha;
        var p = provider(el);
        var api = window[p.global];

        if (api && (p.ready ? api.execute : api.render)) return callback(api);

        (waiting[kind] = waiting[kind] || []).push(callback);

        if (loading[kind]) return;
        loading[kind] = true;

        var name = 'formsCaptchaLoaded_' + kind;
        window[name] = function () {
            var api = window[p.global];
            var done = function () {
                (waiting[kind] || []).splice(0).forEach(function (cb) {
                    cb(api);
                });
            };
            p.ready ? api.ready(done) : done();
        };

        var script = document.createElement('script');
        script.src = p.src(el) + '&onload=' + name;
        script.async = true;
        script.defer = true;
        if (nonce) script.nonce = nonce;
        document.head.appendChild(script);
    }

    // reCAPTCHA v3: ask Google for a token as the form is submitted, then submit it again.
    // This listens on the document in the capture phase, so it runs before the site's own
    // submit handlers (an AJAX one, say) and they only ever see a form that has its token.
    function watchSubmit(el) {
        var form = el.closest('form');
        if (!form || form.dataset.formsCaptchaV3) return;
        form.dataset.formsCaptchaV3 = '1';

        document.addEventListener(
            'submit',
            function (event) {
                if (event.target !== form) return;

                var field = input(el);
                // Tokens last two minutes. No less-than signs in this file: Statamic tidies field HTML with a regex that would catch them.
                var fresh = field.value && 90000 > Date.now() - (field.formsCaptchaAt || 0);
                if (fresh) return;

                event.preventDefault();
                event.stopImmediatePropagation();

                if (form.formsCaptchaPending) return;
                form.formsCaptchaPending = true;

                var submitter = event.submitter;

                load(el, function (api) {
                    api.execute(el.dataset.sitekey, { action: el.dataset.action }).then(
                        function (token) {
                            form.formsCaptchaPending = false;
                            field.value = token;
                            field.formsCaptchaAt = Date.now();
                            if (form.requestSubmit) {
                                submitter && submitter.form === form ? form.requestSubmit(submitter) : form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        },
                        function () {
                            form.formsCaptchaPending = false;
                            form.dispatchEvent(new CustomEvent('forms-captcha:error', { bubbles: true }));
                        },
                    );
                });
            },
            true,
        );
    }

    function render(root) {
        (root || document).querySelectorAll('[data-forms-captcha]').forEach(function (el) {
            var p = provider(el);
            if (!p || el.formsCaptchaId !== undefined || el.formsCaptchaRendering) return;
            el.formsCaptchaRendering = true;

            if (el.dataset.formsCaptcha === 'recaptcha_v3') watchSubmit(el);

            load(el, function (api) {
                el.formsCaptchaId = p.render(api, el);
                el.formsCaptchaRendering = false;
            });
        });
    }

    // Tokens are single use, so after a submission, passed or failed, the widget needs a new one.
    function reset(target) {
        var root = target && target.querySelectorAll ? target : document;
        var els = root.matches && root.matches('[data-forms-captcha]') ? [root] : root.querySelectorAll('[data-forms-captcha]');

        Array.prototype.forEach.call(els, function (el) {
            var p = provider(el);
            var api = p && window[p.global];
            if (!api || el.formsCaptchaId === undefined) return;
            try {
                p.reset(api, el.formsCaptchaId, el);
            } catch (e) {}
        });
    }

    // Forms posted with fetch() or XMLHttpRequest (axios uses it) reset their captcha when the
    // response comes back. Precognition requests don't check the captcha, so they're left alone.
    // Never throws: it runs before every fetch() and XMLHttpRequest on the page.
    function formsFor(url) {
        try {
            var href = absolute(url);

            return Array.prototype.filter.call(document.forms, function (form) {
                // getAttribute, as a field named "action" would hide form.action.
                var action = form.getAttribute('action');
                return action && absolute(action) === href && form.querySelector('[data-forms-captcha]');
            });
        } catch (e) {
            return [];
        }
    }

    function absolute(url) {
        return new URL(url, location.href).href.split('#')[0];
    }

    function precognitive(headers) {
        if (!headers) return false;
        if (typeof Headers !== 'undefined' && headers instanceof Headers) return headers.has('Precognition');
        if (Array.isArray(headers)) {
            return headers.some(function (pair) {
                return String(pair[0]).toLowerCase() === 'precognition';
            });
        }
        return Object.keys(headers).some(function (name) {
            return name.toLowerCase() === 'precognition';
        });
    }

    function afterwards(forms) {
        return function () {
            forms.forEach(reset);
        };
    }

    if (window.fetch) {
        var fetch = window.fetch;
        window.fetch = function (resource, init) {
            var request = typeof Request !== 'undefined' && resource instanceof Request ? resource : null;
            var forms = formsFor(request ? request.url : String(resource));
            var promise = fetch.apply(this, arguments);

            if (forms.length && !precognitive(init && init.headers) && !(request && request.headers.has('Precognition'))) {
                promise.then(afterwards(forms), afterwards(forms));
            }

            return promise;
        };
    }

    if (window.XMLHttpRequest) {
        var open = XMLHttpRequest.prototype.open;
        var setRequestHeader = XMLHttpRequest.prototype.setRequestHeader;
        var send = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (method, url) {
            this.formsCaptchaUrl = url;
            this.formsCaptchaPrecognitive = false;
            return open.apply(this, arguments);
        };

        XMLHttpRequest.prototype.setRequestHeader = function (name) {
            if (String(name).toLowerCase() === 'precognition') this.formsCaptchaPrecognitive = true;
            return setRequestHeader.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function () {
            var forms = this.formsCaptchaUrl ? formsFor(this.formsCaptchaUrl) : [];
            if (forms.length && !this.formsCaptchaPrecognitive) this.addEventListener('loadend', afterwards(forms));
            return send.apply(this, arguments);
        };
    }

    window.FormsCaptcha = { render: render, reset: reset };

    document.addEventListener('forms-captcha:reset', function (event) {
        reset(event.target);
    });

    render();
})();
