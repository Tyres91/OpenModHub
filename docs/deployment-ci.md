# CI/CD Pipeline — Build and Publish Docker Image

This guide covers the automated Docker image build pipeline for OpenModHub via GitHub Actions.

## Overview

```
git push main ──▶ GitHub Actions ──▶ Tests ──▶ Build Image ──▶ Push to GHCR
```

The Docker image is only built if all tests pass. This ensures that broken code never reaches the registry.

---

## How It Works

The existing `ci.yml` workflow has two jobs:

1. **`tests-and-build`** — Runs on every push to `main` and every PR
   - Installs PHP and Node dependencies
   - Runs the full test suite (`php artisan test`)
   - Builds frontend assets (`npm run build`)

2. **`docker-publish`** — Runs only after successful tests on push to `main`
   - Builds the production Docker image using `Dockerfile.unraid`
   - Pushes to GitHub Container Registry (GHCR)

### Workflow File

`.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
    branches:
      - main
  pull_request:

jobs:
  tests-and-build:
    runs-on: ubuntu-latest
    steps:
      # ... test steps ...

  docker-publish:
    needs: tests-and-build
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Log in to Container Registry
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ghcr.io/tyres91/openmodhub
          tags: |
            type=sha
            type=raw,value=latest

      - name: Build and push
        uses: docker/build-push-action@v5
        with:
          context: .
          file: Dockerfile.unraid
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
```

---

## Image Tags

| Tag | Description |
|---|---|
| `latest` | Always the most recent successful build from `main` |
| `<sha>` | The full commit SHA (e.g., `a1b2c3d4e5f6...`) |

### Example

```bash
# Always get the newest version
docker pull ghcr.io/tyres91/openmodhub:latest

# Pin to a specific commit
docker pull ghcr.io/tyres91/openmodhub:a1b2c3d4e5f67890
```

---

## What the Build Produces

The `Dockerfile.unraid` creates a production image with:

- **PHP 8.4 CLI** runtime
- **Node.js 22** build stage (compiles React/Tailwind assets via Vite)
- **Composer** dependencies (production only, no dev packages)
- Pre-built frontend assets in `public/build`
- Required PHP extensions: `pdo_mysql`, `gd`, `intl`, `zip`
- Entrypoint script that runs migrations on startup

---

## Registry Access for Servers

To pull the image on a server, you need a Personal Access Token with `packages: read` permission.

1. GitHub → Settings → Developer settings → Personal access tokens → Fine-grained tokens
2. Create token with:
   - **Repository access**: Only the OpenModHub repository
   - **Permissions**: `packages: read`
3. On the server:

```bash
echo <YOUR_TOKEN> | docker login ghcr.io -u <YOUR_GITHUB_USERNAME> --password-stdin
```

---

## Manual Build (Local)

If you need to build an image manually for testing:

```bash
docker build -f Dockerfile.unraid -t openmodhub:local .
docker run --rm openmodhub:local php artisan key:generate --show
```

---

## Security Notes

- The workflow uses `GITHUB_TOKEN` automatically — no secrets need to be configured
- For production servers, use a dedicated read-only token, not your personal token
- Images in GHCR are private by default; set repository visibility to public if needed
