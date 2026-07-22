# Security Policy

## Supported Versions

The following versions of Laravel Idempotency currently receive security updates.

| Version | Supported |
|---------|-----------|
| 0.x | ✅ |
| < 0.1.0 | ❌ |

Only the latest release series receives security fixes.

---

## Reporting a Vulnerability

If you discover a security vulnerability, please **do not open a public GitHub issue**.

Instead, report it privately by email:

**adilazhariosman@gmail.com**

Please include as much information as possible:

- Package version
- PHP version
- Laravel version
- Steps to reproduce
- Proof of concept (if available)
- Potential impact

I will acknowledge receipt of your report as soon as possible and investigate the issue.

If the report is confirmed, I will:

1. Develop and test a fix.
2. Publish a patched release.
3. Credit the reporter (unless anonymity is requested).

---

## Scope

Examples of security issues include:

- Authentication or authorization bypasses
- Request replay vulnerabilities
- Locking or race condition bypasses
- Idempotency key collision attacks
- Cache poisoning
- Sensitive information disclosure
- Remote code execution
- Denial-of-service vulnerabilities caused by the package

General bugs, feature requests, and documentation improvements should be submitted through GitHub Issues instead.

---

## Disclosure Policy

Please allow reasonable time for the vulnerability to be investigated and resolved before making any public disclosure.

Coordinated disclosure helps protect users while fixes are being prepared and released.

Thank you for helping improve the security of Laravel Idempotency.
