# Temuka Progressive Catalog Search Specification

**Status:** Implementation Baseline v1.0  
**Tujuan:** Spesifikasi teknis endpoint perantara pencarian katalog Temuka Backend ke UIH.

## 1. Alur Layanan

```text
Browser Temuka ──► POST /api/v1/catalog/search (Accept: text/event-stream)
                          │
                          ▼
            CatalogSearchController (Temuka Backend)
                          │
                          ▼
               UihStreamClient (S2S Guzzle Stream)
                          │
                          ▼
               UIH Gateway (/api/v1/catalog/search)
```

## 2. Environment Variables

Tambahkan konfigurasi berikut pada `.env`:
```dotenv
UIH_BASE_URL=http://localhost:8011
UIH_SERVER_TOKEN=uih-dev-internal-token
UIH_SEARCH_TIMEOUT_SECONDS=10
```

## 3. Kontrak Endpoint Frontend $\rightarrow$ Temuka Backend

- **Metode:** `POST`
- **Path:** `/api/v1/catalog/search`
- **Header:**
  - `Accept: text/event-stream`
  - `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "search_id": "search_1730700000_abcde",
    "query": "nasi goreng",
    "limit": 20
  }
  ```
- **Response:**
  - `Content-Type: text/event-stream`
  - `Cache-Control: no-cache, no-transform`
  - `X-Accel-Buffering: no`
  - Mengalirkan event `search_init`, `source_result`, `source_failed`, dan `search_done`.
