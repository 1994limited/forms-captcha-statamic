# Low scores and spam

reCAPTCHA v3 doesn't ask visitors anything. Google scores each visit from 0.0 (probably a bot) to 1.0 (probably a person), and a visit scoring under `score` in `config/forms-captcha.php` (0.5 unless you change it) is a low score.

Each Captcha field chooses what a low score does, under **Low scores** in the builder:

- **Show an error.** The submission is refused, like a failed checkbox captcha, and the visitor sees the error. They can try again, but a bot scoring low will keep scoring low.
- **Keep as spam.** The visitor is told it went through. The submission is kept with the status Spam (find it under the form's submissions in the Control Panel), and no emails or connections are sent for it. On a form that doesn't store submissions, it's dropped.

Keeping low scores as spam means a real person Google wasn't sure about doesn't lose their message. Check the form's spam now and then.

New fields start with the `low_scores` setting in the config file, `reject` unless you change it.

Some things are always refused, whatever the field says: a missing or forged token, a token Google has already seen, a token made for another action, and a check that couldn't reach Google.

## Multi-page forms

The Captcha field is checked on the page it's on, and the submission can't be finished without it, so a bot can't skip its page. A low score on an earlier page still makes the finished submission spam.

## The honeypot

Statamic's honeypot still works alongside the captcha. A submission that fills it in is dropped (or kept as spam, if the form's honeypot is set to) before the captcha matters.
