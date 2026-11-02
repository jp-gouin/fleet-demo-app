# Harbor MCP server (https://github.com/Mauraza/mpc-server-private-registry),
# built from a pinned upstream commit.
FROM golang:1.25 AS build
ARG UPSTREAM=https://github.com/Mauraza/mpc-server-private-registry
ARG COMMIT=780923573499509d9c8f4ea0fd14b07ec0b3df76
WORKDIR /src
RUN git clone "$UPSTREAM" . && git checkout --detach "$COMMIT"
RUN CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o /out/mpc-server .

FROM gcr.io/distroless/static-debian12:nonroot
COPY --from=build /out/mpc-server /mpc-server
EXPOSE 8080
USER nonroot:nonroot
ENTRYPOINT ["/mpc-server"]
