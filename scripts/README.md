# SSH Toggle Scripts

These scripts are used by the Security Vulnerability Manager side effects:

- `enable_weak_ssh.sh` -> sets predictable demo credentials (`student:password123`)
- `disable_weak_ssh.sh` -> sets stronger credential profile

Run manually:

```sh
sh scripts/enable_weak_ssh.sh
sh scripts/disable_weak_ssh.sh
```

In Docker, execute from the project root inside the web container or host shell mounted to the project volume.
