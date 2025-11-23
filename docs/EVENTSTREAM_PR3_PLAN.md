---

## PR3 Planning: Integration & Operations

**Objective:** Finalize the EventStream package for production deployment by delivering robust infrastructure, monitoring, and operational tooling.

### 1. Database Implementations
- **Eloquent EventStore:**
  - Implement `DbEventStoreRepository` and `DbStreamReaderRepository` using Laravel Eloquent for Atomy.
  - Support PostgreSQL and MySQL adapters for high-throughput event persistence.
- **Projection State Repository:**
  - Implement `DbProjectionStateRepository` for durable checkpoint storage.
- **Migration Scripts:**
  - Provide migration scripts for all event, snapshot, and projection tables.

### 2. Production Infrastructure
- **Redis/Memcached ProjectionLock:**
  - Implement `RedisProjectionLock` for distributed pessimistic locking.
  - Support TTL, zombie detection, and force release.
- **Configuration:**
  - Add environment-driven configuration for lock driver, batch sizes, and HMAC secrets.

### 3. Monitoring & Observability
- **Prometheus Metrics:**
  - Expose metrics for event throughput, projection lag, error rates, and lock contention.
- **Dashboards:**
  - Provide Grafana dashboards for real-time event stream and projection health.
- **Alerting:**
  - Integrate with Nexus\Notifier for projection failure and lag alerts.

### 4. Performance Testing
- **Stress Tests:**
  - Simulate high-volume event appends and projection rebuilds.
- **Benchmarks:**
  - Profile batch sizes, lock contention, and query latency.
- **Memory Profiling:**
  - Ensure safe operation with large event streams (1M+ events).

### 5. Deployment & Operations
- **Deployment Guide:**
  - Document production setup, health checks, and runbooks for disaster recovery.
- **Migration Guide:**
  - Provide step-by-step migration for existing aggregates to event sourcing.
- **Admin Commands:**
  - Artisan commands for projection rebuild, force reset, and health checks.

### 6. Milestones & Acceptance Criteria
- [ ] All Atomy repositories implement package contracts (no direct DB access in packages)
- [ ] RedisProjectionLock and DbProjectionStateRepository pass all concurrency and crash recovery tests
- [ ] Prometheus metrics and Grafana dashboards deployed in staging
- [ ] All migration and deployment guides reviewed and tested
- [ ] 100% test pass rate for all new infrastructure components

---

*PR3 will complete the EventStream package for enterprise-grade, horizontally scalable, and observable event sourcing in production.*
