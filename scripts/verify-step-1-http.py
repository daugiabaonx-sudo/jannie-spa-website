#!/usr/bin/env python3
"""
JN STEP 1 — HTTP & Mailpit Acceptance Verification Script
"""
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request
from http.cookiejar import CookieJar


def load_env(path=".env"):
    env = {}
    with open(path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            env[k.strip()] = v.strip()
    return env


def fetch(url, follow_redirects=True):
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl):
            return None

    opener = (
        urllib.request.build_opener()
        if follow_redirects
        else urllib.request.build_opener(NoRedirect)
    )
    req = urllib.request.Request(url, headers={"User-Agent": "JN-Step1-Verifier/1.0"})
    try:
        resp = opener.open(req, timeout=10)
        return resp.getcode(), dict(resp.info()), resp.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace") if e.fp else ""
        return e.code, dict(e.headers), body


def main():
    env = load_env()
    base = env.get("WP_HOME", "http://localhost:8080").rstrip("/")
    slug = env.get("WP_LOGIN_SLUG", "phong-lam-viec-7429")
    admin_user = env.get("WP_ADMIN_USER")
    admin_pass = env.get("WP_ADMIN_PASSWORD")

    print("=== 1. Front-end Homepage Check (GET /) ===")
    code, headers, html = fetch(f"{base}/", follow_redirects=False)
    print(f"HTTP Status     : {code}")
    lang_match = re.search(r'<html[^>]*\blang=["\']([^"\']+)["\']', html, re.I)
    print(f"HTML lang attr  : {lang_match.group(1) if lang_match else 'NOT FOUND'}")
    wp_content_count = html.count("/wp-content/")
    wp_json_count = html.count("/wp-json/")
    api_url_count = html.count(f"{base}/api/")
    app_url_count = html.count(f"{base}/app/")
    print(f"Occurrences of '/wp-content/' : {wp_content_count}")
    print(f"Occurrences of '/wp-json/'    : {wp_json_count}")
    print(f"Occurrences of '{base}/api/'  : {api_url_count}")
    print(f"Occurrences of '{base}/app/'  : {app_url_count}")

    print("\n=== 2. Logged-Out /wp-login.php & /wp-admin/ Check (must be 404, no leak) ===")
    for path in ["/wp-login.php", "/wp-admin/"]:
        c, h, b = fetch(f"{base}{path}", follow_redirects=False)
        loc = h.get("Location", h.get("location", "NONE"))
        leaked_slug = slug in b or slug in str(h)
        has_login_form = 'name="log"' in b or 'id="loginform"' in b
        print(
            f"GET {path:<15} -> HTTP {c} | Location: {loc} | Leaks '{slug}': {leaked_slug} | Login form in body: {has_login_form}"
        )

    print(f"\n=== 3. Custom Login Endpoint Check (/{slug}) ===")
    c_get, h_get, b_get = fetch(f"{base}/{slug}", follow_redirects=True)
    has_form = 'id="loginform"' in b_get
    print(f"GET /{slug} -> HTTP {c_get} | Has #loginform: {has_form}")

    # Perform actual POST login at /<LOGIN_SLUG>/
    cj = CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
    opener.open(f"{base}/{slug}/", timeout=10)
    post_data = urllib.parse.urlencode(
        {
            "log": admin_user,
            "pwd": admin_pass,
            "wp-submit": "Đăng nhập",
            "redirect_to": f"{base}/wp-admin/",
            "testcookie": "1",
        }
    ).encode("utf-8")
    req = urllib.request.Request(
        f"{base}/{slug}/",
        data=post_data,
    )
    resp = opener.open(req, timeout=10)
    final_url = resp.geturl()
    admin_body = resp.read().decode("utf-8", errors="replace")
    logged_in_cookies = [c.name for c in cj if c.name.startswith("wordpress_logged_in_")]
    print(
        f"POST /{slug}/ -> HTTP {resp.getcode()} | Final URL: {final_url} | Logged-in cookie set: {bool(logged_in_cookies)} | Admin bar present: {'wp-admin-bar' in admin_body}"
    )

    print("\n=== 4. Mailpit Password-Reset Email Verification ===")
    mp_code, _, mp_body = fetch("http://localhost:8025/api/v1/messages")
    mp_data = json.loads(mp_body)
    print(f"Mailpit API Status: {mp_code} | Total messages in Mailpit: {mp_data.get('total', 0)}")
    for msg in mp_data.get("messages", []):
        print(
            f"  - ID: {msg.get('ID')} | From: {msg.get('From', {}).get('Address')} ({msg.get('From', {}).get('Name')}) "
            f"| To: {[t.get('Address') for t in msg.get('To', [])]} | Subject: {msg.get('Subject')}"
        )


if __name__ == "__main__":
    main()
