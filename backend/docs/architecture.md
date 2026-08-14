# Architecture Decisions

## ADR-001: Service Layer Pattern

**Decision:** Use a dedicated service layer between controllers and models for business logic.

**Context:** Controllers were becoming fat with business logic mixed into HTTP handling.

**Consequences:**
- Better separation of concerns
- Easier unit testing of business logic
- Reusable logic across controllers and jobs
- More verbose file structure

## ADR-002: Queue-Based Processing for Heavy Operations

**Decision:** Use Laravel queue system for email notifications, thumbnail generation, virus scanning, bulk updates, and exports.

**Context:** Synchronous processing of file operations and emails caused slow API responses.

**Consequences:**
- Improved API response times
- Better user experience
- Retry mechanism for failed jobs
- Requires running queue worker
- Added complexity in debugging async flows

## ADR-003: JWT Authentication

**Decision:** Use tymon/jwt-auth for API authentication instead of session-based auth.

**Context:** API needed stateless authentication for mobile/frontend clients.

**Consequences:**
- Stateless API authentication
- Standard JWT token format
- Token expiration and refresh handling
- Cannot invalidate individual tokens easily (except logout)

## ADR-004: Private File Storage with Quarantine

**Decision:** Store uploaded files in private storage with quarantine system for infected files.

**Context:** Files needed to be secure and not directly accessible via URL.

**Consequences:**
- Files served through authenticated endpoints
- Virus scanning simulation with quarantine
- Thumbnails stored separately
- Requires download endpoints for access

## ADR-005: Chunked Upload System

**Decision:** Implement custom chunked upload system for files >50MB.

**Context:** Large file uploads failed due to PHP upload limits and timeouts.

**Consequences:**
- Support for arbitrarily large files
- Better progress tracking
- Resumable uploads possible
- Additional database records for tracking
- Cleanup of expired uploads needed

## ADR-006: File Versioning

**Decision:** Implement file versioning when same filename is uploaded to same task.

**Context:** Users needed to track changes to uploaded files.

**Consequences:**
- Multiple versions stored per attachment
- Disk space usage increases
- Version restoration available
- Old versions can be cleaned up

## ADR-007: Database Queue Driver

**Decision:** Use database queue driver instead of Redis for production.

**Context:** Simplicity of deployment and no additional infrastructure required.

**Consequences:**
- No Redis dependency
- Easier setup for small teams
- Lower performance at scale
- Jobs stored in database tables

## ADR-008: Laravel Scalar for API Documentation

**Decision:** Use scalar/laravel for interactive API documentation.

**Context:** Need for auto-generated, interactive API docs that stay in sync with code.

**Consequences:**
- Auto-discovered routes
- Interactive API testing interface
- OpenAPI spec generation
- Additional dependency

## ADR-009: Intervention Image for Thumbnails

**Decision:** Use intervention/image with GD driver for thumbnail generation.

**Context:** Need consistent thumbnail generation across image types.

**Consequences:**
- Support for common image formats
- Configurable thumbnail size
- Additional queue job for async processing
- Requires GD extension

## ADR-010: Laravel 13 Architecture

**Decision:** Use Laravel 13 with new bootstrap/app.php configuration style.

**Context:** Modern Laravel features and simplified structure.

**Consequences:**
- No separate Kernel.php files
- Configuration via bootstrap/app.php
- Auto-discovery of providers
- Modern PHP 8.2+ features used
