# CI/CD Pipeline — Build and Publish Docker Image

This guide covers building the OpenModHub Docker image with GitHub Actions and publishing it to GitHub Container Registry (GHCR).

## Overview

```
git push ──▶ GitHub Actions ──▶ Build Image ──▶ Push to GHCR
```

The pipeline automatically builds a production-ready image on every push to `main` and on every release.

---

## Prerequisites

- GitHub repository with the OpenModHub code
- Dockerfile: `Dockerfile.unraid` (multi-stage build with frontend assets + PHP app)

---

## GitHub Actions Workflow

Create `.github/workflows/docker-publish.yml`:

```yaml
name: Build and Publish Docker Image

on:
  push:
    branches: [main]
  release:
    types: [published]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build-and-push:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Log in to Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=sha

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

The workflow produces the following tags:

| Trigger | Tag Example |
|---|---|
| Push to `main` | `ghcr.io/user/openmodhub:main` |
| Release `v1.0.0` | `ghcr.io/user/openmodhub:1.0.0`, `ghcr.io/user/openmodhub:latest` |
| Any commit | `ghcr.io/user/openmodhub:<commit-sha>` |

---

## Manual Build (Local)

If you need to build and push an image manually:

### Authenticate

```bash
echo <YOUR_TOKEN> | docker login ghcr.io -u <YOUR_GITHUB_USERNAME> --password-stdin
```

### Build

```bash
docker build -f Dockerfile.unraid -t ghcr.io/<YOUR_GITHUB_USERNAME>/openmodhub:latest .
```

### Push

```bash
docker push ghcr.io/<YOUR_GITHUB_USERNAME>/openmodhub:latest
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

## Security Notes

- The workflow uses `GITHUB_TOKEN` automatically — no secrets need to be configured
- For production servers, use a dedicated read-only token, not your personal token
- Images in GHCR are private by default; set repository visibility to public if needed
