# JavaScript

## Forms sent with fetch() or axios

Captcha tokens can only be used once, so after a form is submitted the widget needs a new one. Forms Captcha watches `fetch()` and `XMLHttpRequest` (which axios uses), and resets a form's widget when a request to that form's `action` URL comes back, passed or failed. You don't need to do anything.

```js
form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
    });

    // The captcha has already been reset for the next try.
    const json = await response.json();
    if (!response.ok) showErrors(json.error); // json.error.captcha when the captcha failed
});
```

## reCAPTCHA v3

With reCAPTCHA v3 the token is fetched from Google as the form is submitted. Forms Captcha's submit listener runs before yours, holds the event until the token is in the form, then submits again, so your handler only ever sees a form with its token. If Google can't be reached, the form isn't sent and a `forms-captcha:error` event is dispatched on it, so you can tell the visitor:

```js
form.addEventListener('forms-captcha:error', () => showError('Please try again in a moment.'));
```

## The JavaScript API

If you post somewhere other than the form's `action`, or add forms to the page after it loads, use the JavaScript API:

```js
FormsCaptcha.render(element); // render widgets added to the page
FormsCaptcha.reset(form);     // get a new token for a form's widget
```

## Precognition

Precognition requests (`$form.validate()` with Alpine) don't check the captcha, so they never use up the token. The captcha is checked when the form is submitted. Alpine's `$form.submit()` sends Alpine's data only, so send the form with `fetch()` and `new FormData(form)` instead, or copy the token into your Alpine data.

## Events

| Event | On | When |
| --- | --- | --- |
| `forms-captcha:error` | the form | reCAPTCHA v3 couldn't get a token from Google, so the form wasn't sent. |
| `forms-captcha:reset` | any element | Dispatch it to reset the widgets inside that element. |
