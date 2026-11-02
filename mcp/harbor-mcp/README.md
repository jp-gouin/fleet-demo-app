# Harbor MCP server

Runs [Mauraza/mpc-server-private-registry](https://github.com/Mauraza/mpc-server-private-registry)
(pinned to `7809235`) in the lab-downstream cluster, pointed at the in-cluster
Harbor (`harbor/values.yaml`). It exposes Harbor scanning, SBOM, and registry
tools over MCP Streamable HTTP.

- MCP endpoint: `http://harbor-mcp.10.144.103.243.sslip.io/mpc-spr`
- Auth: `Authorization: Bearer <MCP_AUTH_TOKEN>`
- Image: `ghcr.io/jp-gouin/harbor-mcp-server:7809235` (built by
  `.github/workflows/harbor-mcp-image.yml`)

## Secrets (not in git)

```sh
export KUBECONFIG=~/.kube/lab-downstream.yaml
HARBOR_PW="$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)Aa1"

kubectl create namespace harbor
kubectl -n harbor create secret generic harbor-admin \
  --from-literal=HARBOR_ADMIN_PASSWORD="$HARBOR_PW"

kubectl create namespace harbor-mcp
kubectl -n harbor-mcp create secret generic harbor-mcp \
  --from-literal=HARBOR_PASSWORD="$HARBOR_PW" \
  --from-literal=MCP_AUTH_TOKEN="$(openssl rand -hex 32)"
```

## Deploy

```sh
helm upgrade --install harbor harbor/harbor --version 1.19.4 -n harbor -f harbor/values.yaml
kubectl apply -f mcp/harbor-mcp/k8s/
```

## Smoke test

```sh
TOKEN="$(kubectl -n harbor-mcp get secret harbor-mcp -o jsonpath='{.data.MCP_AUTH_TOKEN}' | base64 -d)"
curl -i http://harbor-mcp.10.144.103.243.sslip.io/mpc-spr \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  --data '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"curl","version":"1.0"}}}'
```

Expect `200 OK` and an `Mcp-Session-Id` header.
