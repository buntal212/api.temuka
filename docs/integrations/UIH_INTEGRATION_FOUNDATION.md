# Fondasi Integrasi UIH — Temuka Backend

**Dokumen Status:** Approved Technical Reference  
**Target Repository:** `/home/duro/projects/AIPrijects/temuka/api.temuka`  
**Dokumen Acuan Utama:** [UIH Real ASU Integration Foundation Plan](../../../../uih/api.uih/docs/integrations/REAL_ASU_INTEGRATION_FOUNDATION_PLAN.md)  
**Dokumen Arsitektur Lokal:** [`docs/architecture/TEMUKA_PROGRESSIVE_CATALOG_SEARCH.md`](../architecture/TEMUKA_PROGRESSIVE_CATALOG_SEARCH.md)

---

## 1. Peran Temuka Backend dalam Integrasi

Temuka Backend (`api.temuka`) bertindak sebagai **gerbang perantara (*reverse proxy streaming*)** antara antarmuka pengguna Temuka Web (`web.temuka`) dan Udumbara Internal Hub (`api.uih`).

Temuka Backend **tidak berkomunikasi langsung** dengan Aplikasi Sumber Udumbara (ASU) seperti Larissama, melainkan murni meneruskan permintaan pencarian katalog ke UIH melalui koneksi Server-Sent Events (SSE) berautentikasi S2S:

```
[Browser: web.temuka]
         │
         ▼ (SSE / HTTP GET/POST: /api/v1/catalog/search)
[Temuka Backend: api.temuka]
         │
         ▼ (S2S SSE Stream: POST /api/v1/catalog/search, Authorization: Bearer <UIH_TOKEN>)
[UIH Backend: api.uih]
         │
         ▼ (S2S HTTP REST)
[Aplikasi Sumber Udumbara: Larissama, dll.]
```

---

## 2. Fase Integrasi yang Relevan

Berdasarkan dokumen rencana induk di UIH ([`REAL_ASU_INTEGRATION_FOUNDATION_PLAN.md`](../../../../uih/api.uih/docs/integrations/REAL_ASU_INTEGRATION_FOUNDATION_PLAN.md)), fase kerja yang melibatkan Temuka Backend meliputi:

1. **Fase 0 — Docker Networking Baseline & Preflight:**
   Memastikan container `temuka-dev-app` terhubung ke bridge network bersama `temuka-uih-network` dan dapat menjangkau `uih-dev-app:8000`.
2. **Fase 4 — Temuka Backend Routing & Correlation Tracing:**
   - Menyelaraskan batas timeout pencarian pada `UIH_SEARCH_TIMEOUT_SECONDS` di file `.env` Temuka Backend agar proporsional terhadap total alokasi waktu agregasi UIH.
   - Mengirimkan header penelusuran `X-Correlation-ID` pada request keluar ke UIH untuk observabilitas terdistribusi.
   - Memastikan socket streaming ditutup dengan bersih saat event `search_done` diterima.
3. **Fase 6 — Integrasi Real Adapter Larissama (End-to-End Test):**
   Memvalidasi penerusan data menu nyata dari Larissama yang diagregasi oleh UIH agar mengalir mulus ke Temuka Backend tanpa buffering macet.
4. **Fase 7 — Verifikasi Komprehensif & Observabilitas:**
   Uji beban, latensi, dan ketahanan koneksi streaming di bawah variasi latensi ASU.

---

## 3. Pengelolaan Dokumentasi

Dokumentasi spesifik Temuka Backend (konvensi coding, pengujian, panduan Docker, dan arsitektur internal) tetap dikelola secara mandiri di dalam direktori `docs/` repositori ini:
- Panduan Alur Kerja Agent: [`docs/engineering/AGENT_WORKFLOW.md`](../engineering/AGENT_WORKFLOW.md)
- Konvensi Backend: [`docs/engineering/BACKEND_CONVENTIONS.md`](../engineering/BACKEND_CONVENTIONS.md)
- Pengujian Backend: [`docs/engineering/TESTING.md`](../engineering/TESTING.md)

Rincian menyeluruh mengenai rancangan adapter, normalisasi kanonik, dan pendaftaran ASU diatur secara terpusat pada dokumen utama di repositori UIH:
👉 **[REAL_ASU_INTEGRATION_FOUNDATION_PLAN.md](../../../../uih/api.uih/docs/integrations/REAL_ASU_INTEGRATION_FOUNDATION_PLAN.md)**
