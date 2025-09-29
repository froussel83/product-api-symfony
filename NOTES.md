# Notes

## 1) Key design choices

 * **DTOs + Validator**: CreateProductDto, UpdateProductDto, IdDto keep HTTP input separate from Doctrine entities and centralize validation.
 * **Application service**: ProductService owns use-case logic (validate, generate SKU, persist, update). Controllers are thin I/O layers.
 * **Stateless SKU generation**: SkuGenerator::generate(name) -> PROD-{FIRST4}-{hex7}, easy to test and scale.
 * **Error model**: custom exceptions (ValidationException, NotFoundException) mapped to JSON via ApiExceptionSubscriber -> consistent 422/404/400.
 * **Representation**: ProductTransformer formats responses, entity stays persistence-focused.
 * **IDs**: UUID for opaque, non-sequential identifiers.
 * **Testing**: Unit tests for SKU, functional tests for success + errors, with Faker for variability.

## 2) Risks / trade-offs

 * **SKU collisions**: low probability but non-zero; must have DB unique and retry on conflict.
 * **Idempotency**: POST can create duplicates on client retries; consider Idempotency-Key support.
 * **Price as float**: rounding/precision issues. Prefer DECIMAL (or integer minor units) + Money value object.
 * **Security not specified**: needs AuthN/Z, rate limiting, and CORS for real clients.

## 3) Production hardening (high-traffic)

 * **Data correctness**: switch price to DECIMAL(10,2), store times in UTC; keep UNIQUE(sku) and implement retry on collision.
 * **Performance & scale**: stateless app with OPcache, caching for GET.
 * **Reliability**: health checks, timeouts/circuit breakers around DB, zero-downtime migrations (expand/migrate/contract).
 * **Security**: JWT/OAuth2, per-route authorization, rate limiting, secrets management, TLS everywhere.
 * **Observability (Graylog)**: centralized, structured JSON logs via Monolog GELF, with a per-request correlation ID (X-Request-Id); build Graylog streams/dashboards and alerts (e.g., spikes in 5xx). Optionally add Prometheus/Grafana later for request/DB metrics.
 * **DX/Quality (SonarQube)**: CI-enforced Quality Gate (bugs/vulns/code smells + coverage from PHPUnit Clover), plus static analysis (PHPStan/Psalm). Keep an OpenAPI spec if/when the API surface grows.
