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

## 3. Keputusan Arsitektur & Spesifikasi Fase 4

### A. Penyelarasan Batas Limit
- Batas maksimal pencarian katalog (`limit`) diselaraskan secara konsisten antara Temuka Backend dan UIH Gateway:
  - Nilai minimal: `1`
  - Nilai maksimal: `50` (sebelumnya `100` di Temuka)
  - Nilai default: `20`
- Permintaan dengan nilai `limit` > 50 atau < 1 ditolak dengan status HTTP `422 Unprocessable Content`.

### B. Correlation ID & Distributed Tracing
- `X-Correlation-ID` digunakan untuk distributed tracing lintas service (`Temuka Web → Temuka Backend → UIH Gateway → ASU`).
- Temuka menerima `X-Correlation-ID` dari header request (bila disediakan caller).
- Format divalidasi sebagai UUID valid (`Str::isUuid`). Apabila kosong, tidak ada, atau tidak valid, Temuka membangkitkan UUID baru secara otomatis (`Str::uuid()`).
- Header `X-Correlation-ID` diteruskan dari Temuka ke UIH melalui `UihStreamClient` dan dikembalikan pada response header SSE Temuka (`X-Correlation-ID: <uuid>`).
- Nilai `search_id` dan `correlation_id` tetap entitas terpisah: `search_id` mengidentifikasi query/sesi pencarian, sedangkan `correlation_id` untuk distributed tracing.

### C. Keamanan Log & Error Handling
- Pada saat terjadi kegagalan jaringan atau timeout saat menghubungkan ke UIH Gateway, exception detail (seperti internal host, port, atau rute internal) dicatat ke log server terstruktur (`Log::warning`) beserta `correlation_id` dan `search_id`.
- Pesan kesalahan pada payload SSE (`GATEWAY_CONNECTION_ERROR`) menggunakan pesan aman yang ramah pengguna (`Tidak dapat terhubung ke UIH Gateway.`) dan tidak membocorkan detail internal atau kredensial rahasia.

### D. Keputusan Arsitektur Pagination
- **Tanggung Jawab UIH:** UIH bertindak sebagai pengelola pagination dan agregasi data dari masing-masing ASU.
- **Model Inkremental SSE:** SSE mengalirkan hasil per-sumber (`source_result`) secara inkremental dan progresif, bukan berbasis pagination global atau offset ranking.
- **Urutan Kedatangan (Arrival Order):** Frontend menampilkan produk sesuai urutan kedatangan stream dari ASU tercepat; tidak ada global ranking lintas ASU yang menunda streaming.
- **Status Kelanjutan (`has_more`):** Status kelanjutan pencarian atau ketersediaan data lebih lanjut ditentukan dan dikontrol oleh UIH, bukan oleh Temuka.
- **Larangan Scope Fase 4:** Tidak menambahkan event atau field pagination baru pada SSE stream pada fase ini; perubahan tersebut dialokasikan pada fase terpisah setelah kontrak ASU nyata disepakati.

---

## 4. Pengelolaan Dokumentasi

Dokumentasi spesifik Temuka Backend (konvensi coding, pengujian, panduan Docker, dan arsitektur internal) tetap dikelola secara mandiri di dalam direktori `docs/` repositori ini:
- Panduan Alur Kerja Agent: [`docs/engineering/AGENT_WORKFLOW.md`](../engineering/AGENT_WORKFLOW.md)
- Konvensi Backend: [`docs/engineering/BACKEND_CONVENTIONS.md`](../engineering/BACKEND_CONVENTIONS.md)
- Pengujian Backend: [`docs/engineering/TESTING.md`](../engineering/TESTING.md)

Rincian menyeluruh mengenai rancangan adapter, normalisasi kanonik, dan pendaftaran ASU diatur secara terpusat pada dokumen utama di repositori UIH:
👉 **[REAL_ASU_INTEGRATION_FOUNDATION_PLAN.md](../../../../uih/api.uih/docs/integrations/REAL_ASU_INTEGRATION_FOUNDATION_PLAN.md)**
