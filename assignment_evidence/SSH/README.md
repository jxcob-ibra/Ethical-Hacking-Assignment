# Weak SSH Credentials

- Vulnerability: predictable credentials are set when `weak_ssh_credentials` is enabled.
- Attack steps: run `scripts/enable_weak_ssh.sh`, then test `student/password123`.
- Remediation: disable vulnerability or run `scripts/disable_weak_ssh.sh`.
- Re-test: attempt old weak credential after disabling.
- Required screenshots: script output ON/OFF, auth success with weak cred, auth failure after disable.
- Suggested tools: Hydra, SSH Client.
- Expected before fix: predictable credential accepted.
- Expected after fix: weak credential rejected.
