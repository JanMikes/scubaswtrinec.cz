# Guest book ("Diskuze") — spam defence

## What happened
Between 2026-01-05 and 2026-07-28 a bot posted 11 entries (ids 24–34) whose every field was a random
mixed-case letter string. The 2014 antispam (hidden "clear this field" input + 5 s delay) did not stop
it, and the old app logged the Docker proxy IP (172.18.x) instead of the client's, so IP bans could
never match. Migration `Version20261022100000` soft-deletes exactly those 11 rows (`del_flag = 1`,
same as the 2014 viagra spam ids 10–15); `down()` restores them.

## Layers (cheapest first) — `src/Controller/DiscussionController.php`
1. **Honeypot** field (`website`, hidden by CSS) and a **signed fill-time token**
   (`FormTimeToken`, HMAC with the kernel secret): reject faster than 3 s or older than 6 h.
2. **IP**: the real client IP (Traefik `X-Forwarded-For`, trusted only from loopback / RFC 1918 —
   `config/packages/framework.yaml`), checked against `discussion_ban` and a **rate limit** of 3 posts
   per 10 minutes per IP (sliding window).
3. **CSRF** token on the form; everything is rendered escaped (Twig), e-mail addresses are never shown
   publicly.
4. **Content rules** (`src/Guestbook/SpamClassifier.php`, unit-tested against every real spam and
   every real legitimate post in `tests/fixtures/guestbook/`):
   - reject: two or more fields that are random mixed-case letter runs; `<a href>` / BBCode links;
     more than 5 links;
   - hold for moderation (stored hidden, `active_flag = 0`): one such field, HTML markup, 3–5 links,
     a link in the name.
5. Rejections get one generic message ("Příspěvek se nepodařilo odeslat…") and one log line in channel
   `guestbook` with the reason, IP and user agent (docker logs → Loki: `{app="scubaswtrinec"} |= "Guest-book"`).

## Moderation
Admin → Diskuze → **Ke schválení** lists held posts; **Schválit** publishes, **Smazat** soft-deletes,
**Zablokovat** bans the poster's IP. The dashboard shows a banner while anything is waiting.

## Escalation path (not implemented — evidence so far does not need it)
If bots get past the layers above (watch the `guestbook` log lines and the "Ke schválení" queue), add a
self-hosted proof-of-work captcha: **ALTCHA** (`altcha-org/altcha` PHP library for the challenge +
verification, the `altcha` web component from npm via AssetMapper). No third party sees visitors, no
cookies, invisible for humans apart from a short "verifying" tick. Third-party captchas (reCAPTCHA,
hCaptcha, Turnstile) were not chosen: privacy/consent burden for a small club site.
