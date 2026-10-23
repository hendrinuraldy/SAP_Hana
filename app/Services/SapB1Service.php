<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class SapB1Service
{
    protected string $baseUrl;
    protected bool $verifySsl;

    // dipakai khusus untuk proses sync (console command / scheduler), bukan session user browser
    protected ?string $syncSessionId = null;
    protected ?string $syncCookie = null;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.sap_b1.base_url'), '/');
        $this->verifySsl = config('services.sap_b1.verify_ssl', false);
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withOptions(['verify' => $this->verifySsl])
            ->acceptJson()
            ->timeout(30);
    }

    protected function extractSessionCookie($response): ?string
    {
        foreach ($response->cookies() as $cookie) {
            if ($cookie->getName() === 'B1SESSION') {
                return $cookie->getValue();
            }
        }
        return null;
    }

    // ================== LOGIN UNTUK USER YANG BROWSING WEB ==================

    public function login(string $companyDb, string $username, string $password): ?array
    {
        $response = $this->client()->post('/Login', [
            'CompanyDB' => $companyDb,
            'UserName'  => $username,
            'Password'  => $password,
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        Session::put('sap_b1_session_id', $data['SessionId']);
        Session::put('sap_b1_company_db', $companyDb);
        Session::put('sap_b1_session_expires_at', now()->addMinutes($data['SessionTimeout'] ?? 30));
        Session::put('sap_b1_cookie', $this->extractSessionCookie($response));

        return $data;
    }

    public function logout(): void
    {
        if (Session::has('sap_b1_session_id')) {
            $this->authorizedClient()->post('/Logout');
        }
        Session::forget(['sap_b1_session_id', 'sap_b1_company_db', 'sap_b1_session_expires_at', 'sap_b1_cookie']);
    }

    public function isLoggedIn(): bool
    {
        return Session::has('sap_b1_session_id')
            && now()->lessThan(Session::get('sap_b1_session_expires_at'));
    }

    public function authorizedClient()
    {
        $sessionId = Session::get('sap_b1_session_id');
        return $this->client()->withHeaders([
            'Cookie' => "B1SESSION={$sessionId}",
        ]);
    }

    /**
     * Ambil semua baris dari sebuah entity Service Layer dengan mengikuti
     * pagination (odata.nextLink) sampai habis. Dipakai oleh getPickLists(),
     * getSalesOrders(), dan laporan lain yang butuh data lengkap (bukan cuma
     * halaman pertama).
     *
     * PENTING: Service Layer membatasi jumlah baris per-request (default
     * sekitar 20, tergantung config server SAP). Tanpa ikuti odata.nextLink,
     * hasil laporan akan kepotong di halaman pertama saja.
     */
    /**
     * Ambil semua baris dari sebuah entity Service Layer dengan mengikuti
     * pagination (odata.nextLink) sampai habis. Dipakai oleh getPickLists(),
     * getSalesOrders(), dan laporan lain yang butuh data lengkap (bukan cuma
     * halaman pertama).
     *
     * PENTING soal performa: Service Layer default cuma balikin ~20 baris
     * per-request. Untuk data yang jumlahnya ribuan (mis. 3000 Purchase
     * Order), itu bisa jadi 150 kali round-trip berurutan ke SAP -- inilah
     * penyebab utama laporan terasa lambat, BUKAN cuma soal banyaknya data.
     * Makanya di sini kita minta $top yang jauh lebih besar (default 500)
     * supaya jumlah request ke SAP jauh berkurang.
     *
     * $expand dipakai untuk collection nested (mis. DocumentLines) supaya
     * tetap dapat semua field di dalamnya secara utuh, sambil $select tetap
     * bisa dipakai untuk mempersempit field level atas saja (mengurangi
     * ukuran payload tanpa memotong field di dalam nested collection seperti
     * yang terjadi kalau nama collection ikut dimasukkan ke $select).
     */
    protected function fetchAllWithPagination(
        string $endpoint,
        ?string $filter = null,
        ?string $select = null,
        ?string $expand = null,
        int $top = 500
    ): array {
        $query = ['$top' => $top];
        if ($filter) $query['$filter'] = $filter;
        if ($select) $query['$select'] = $select;
        if ($expand) $query['$expand'] = $expand;

        $client = $this->authorizedClient();
        $all = [];

        $response = $client->get("/{$endpoint}", $query);

        if ($response->failed()) {
            throw new \RuntimeException("Gagal mengambil {$endpoint}: " . $response->body());
        }

        $data = $response->json();
        $all = array_merge($all, $data['value'] ?? []);

        while (isset($data['odata.nextLink'])) {
            $response = $client->get($data['odata.nextLink']);
            if ($response->failed()) {
                throw new \RuntimeException("Gagal mengambil halaman berikutnya {$endpoint}: " . $response->body());
            }
            $data = $response->json();
            $all = array_merge($all, $data['value'] ?? []);
        }

        return $all;
    }

    /**
     * Ambil data Pick List lewat REST entity resmi Service Layer (bukan raw SQL),
     * makanya tidak kena batasan ad-hoc query / akses tabel mentah.
     */
    public function getPickLists(?string $filter = null, ?string $select = null, ?string $expand = null): array
    {
        return $this->fetchAllWithPagination('PickLists', $filter, $select, $expand);
    }

    /**
     * Ambil data Sales Order (header + DocumentLines) lewat Service Layer.
     * Dipakai untuk laporan Sales Order Report.
     */
    public function getSalesOrders(?string $filter = null, ?string $select = null, ?string $expand = null): array
    {
        return $this->fetchAllWithPagination('Orders', $filter, $select, $expand);
    }

    /**
     * Ambil data Purchase Order (header + DocumentLines) lewat Service Layer.
     * Dipakai untuk laporan Purchase Order Report.
     */
    public function getPurchaseOrders(?string $filter = null, ?string $select = null, ?string $expand = null): array
    {
        return $this->fetchAllWithPagination('PurchaseOrders', $filter, $select, $expand);
    }

    /**
     * Ambil data Journal Entry (jurnal umum) lewat Service Layer.
     * Entity ini strukturnya beda dari Orders/PurchaseOrders: tidak ada
     * DocumentStatus, field header pakai ReferenceDate (bukan DocDate), dan
     * baris jurnalnya (JournalEntryLines) berisi Debit/Credit per akun,
     * bukan ItemCode/Quantity.
     */
    public function getJournalEntries(?string $filter = null, ?string $select = null, ?string $expand = null): array
    {
        return $this->fetchAllWithPagination('JournalEntries', $filter, $select, $expand);
    }

    /**
     * Ambil 1 dokumen basis (mis. Sales Order / A/R Invoice) berdasarkan DocEntry.
     * Dipakai untuk enrich ItemCode/ItemName di Picking List Report, karena
     * entity PickListsLines sendiri tidak menyimpan info item sama sekali
     * (hanya OrderEntry/OrderRowID yang merujuk ke baris dokumen basis).
     *
     * Contoh: getDocumentByEntry('Orders', 123880, 'DocumentLines')
     * -> GET /Orders(123880)?$select=DocumentLines
     */
    public function getDocumentByEntry(string $endpoint, int $docEntry, ?string $select = null): array
    {
        $query = [];
        if ($select) {
            $query['$select'] = $select;
        }

        $response = $this->authorizedClient()->get("/{$endpoint}({$docEntry})", $query);

        if ($response->failed()) {
            throw new \RuntimeException("Gagal mengambil {$endpoint}({$docEntry}): " . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Validasi ketat untuk SQL ad-hoc sebelum dikirim ke SAP. Menolak apapun
     * selain satu statement SELECT tunggal tanpa keyword berbahaya.
     *
     * Kenapa perlu ini (bukan cuma cek stripos di awal string):
     * - "select 1; drop table ..."      -> statement kedua nyelundup lewat ";"
     * - "select 1 -- ; anything"        -> komentar SQL bisa menyembunyikan niat asli
     * - "select * from (delete ...)"    -> keyword berbahaya taruh di subquery
     * - "SeLeCt ..."                    -> stripos case-sensitive tidak selalu cocok
     */
    protected function sanitizeAdHocSql(string $sqlText): string
    {
        $sqlText = trim($sqlText);

        if ($sqlText === '') {
            throw new \RuntimeException('Query tidak boleh kosong.');
        }

        // Hilangkan trailing semicolon tunggal yang wajar (mis. user ngetik "select ...;")
        $normalized = rtrim($sqlText);
        $normalized = rtrim($normalized, ';');

        // Kalau setelah dibuang satu ";" di akhir masih ada ";" lain di tengah,
        // berarti ini multi-statement -> tolak.
        if (str_contains($normalized, ';')) {
            throw new \RuntimeException('Query tidak boleh mengandung lebih dari satu statement (";").');
        }

        // Tolak komentar SQL (-- atau /* */), karena bisa dipakai menyembunyikan
        // sisa query atau melewati filter kata kunci di atas.
        if (str_contains($normalized, '--') || str_contains($normalized, '/*') || str_contains($normalized, '*/')) {
            throw new \RuntimeException('Query tidak boleh mengandung komentar SQL ("--" atau "/* */").');
        }

        // Harus benar-benar diawali SELECT (case-insensitive, boleh ada whitespace di depan).
        if (!preg_match('/^select\s/i', $normalized)) {
            throw new \RuntimeException('Hanya query SELECT yang diperbolehkan.');
        }

        // Tolak keyword berbahaya di manapun posisinya (termasuk di dalam subquery).
        // Pakai word-boundary supaya tidak salah tangkap kata yang cuma "mengandung"
        // substring keyword itu (mis. kolom bernama "created_by").
        foreach (self::SQL_BLOCKED_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $normalized)) {
                throw new \RuntimeException("Query mengandung keyword yang tidak diperbolehkan: \"{$keyword}\".");
            }
        }

        return $normalized;
    }

    /**
     * Menjalankan query SQL ad-hoc yang diketik user secara langsung.
     * Caranya: bikin saved query sementara di SAP, jalankan, lalu hapus lagi.
     * Query harus SELECT (read-only), tidak boleh INSERT/UPDATE/DELETE.
     * Sudah melewati sanitizeAdHocSql() untuk validasi ketat.
     */
    /**
     * Keyword yang tidak boleh muncul sama sekali di query ad-hoc, karena
     * berpotensi mengubah data atau struktur database, atau menyelundupkan
     * statement kedua lewat subquery/komentar.
     */
    protected const SQL_BLOCKED_KEYWORDS = [
        'insert', 'update', 'delete', 'drop', 'alter', 'truncate', 'create',
        'grant', 'revoke', 'exec', 'execute', 'merge', 'call', 'replace',
        'union', 'into', 'xp_', 'sp_',
    ];

    public function runAdHocQuery(string $sqlText): array
    {
        $sqlText = $this->sanitizeAdHocSql($sqlText);

        $tempCode = 'AdHoc_' . uniqid();

        $create = $this->authorizedClient()->post('/SQLQueries', [
            'SqlCode' => $tempCode,
            'SqlName' => 'Temporary Ad-Hoc Query',
            'SqlText' => $sqlText,
        ]);

        if ($create->failed()) {
            throw new \RuntimeException('Gagal membuat query: ' . $create->body());
        }

        $list = $this->authorizedClient()->get("/SQLQueries('{$tempCode}')/List");

        // selalu hapus query sementara ini, sukses maupun gagal, biar tidak numpuk di SAP
        $this->authorizedClient()->delete("/SQLQueries('{$tempCode}')");

        if ($list->failed()) {
            throw new \RuntimeException('Gagal menjalankan query: ' . $list->body());
        }

        return $list->json()['value'] ?? [];
    }

    /**
     * Menjalankan saved query (SQLQueries) yang sudah dibuat di SAP,
     * memakai session interaktif user yang sedang login.
     */
    public function runSavedQuery(string $sqlCode): array
    {
        $response = $this->authorizedClient()->get("/SQLQueries('{$sqlCode}')/List");

        if ($response->failed()) {
            throw new \RuntimeException("Gagal menjalankan query '{$sqlCode}': " . $response->body());
        }

        return $response->json()['value'] ?? [];
    }

    // ================== LOGIN KHUSUS UNTUK PROSES SYNC (console/cron) ==================
    // Tidak pakai session HTTP Laravel, karena command berjalan di CLI tanpa request/browser.

    public function loginForSync(): void
    {
        $response = $this->client()->post('/Login', [
            'CompanyDB' => config('services.sap_b1.company_db'),
            'UserName'  => config('services.sap_b1.sync_username'),
            'Password'  => config('services.sap_b1.sync_password'),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal login SAP untuk sync: ' . $response->body());
        }

        $data = $response->json();
        $this->syncSessionId = $data['SessionId'];
        $this->syncCookie    = $this->extractSessionCookie($response);
    }

    public function syncClient()
    {
        if (!$this->syncSessionId) {
            $this->loginForSync();
        }

        return $this->client()->withHeaders([
            'Cookie' => "B1SESSION={$this->syncSessionId}",
        ]);
    }

    /**
     * Ambil semua data dari sebuah endpoint SAP dengan pagination (odata.nextLink),
     * dipakai oleh console command sync.
     */
    public function fetchAllForSync(string $endpoint, ?string $select = null, int $top = 100): array
    {
        $client = $this->syncClient();
        $all = [];

        $query = ['$top' => $top];
        if ($select) {
            $query['$select'] = $select;
        }

        $response = $client->get($endpoint, $query);

        if ($response->failed()) {
            throw new \RuntimeException("Gagal fetch {$endpoint}: " . $response->body());
        }

        $data = $response->json();
        $all = array_merge($all, $data['value'] ?? []);

        // ikuti pagination selama ada odata.nextLink
        while (isset($data['odata.nextLink'])) {
            $response = $client->get($data['odata.nextLink']);
            if ($response->failed()) {
                break;
            }
            $data = $response->json();
            $all = array_merge($all, $data['value'] ?? []);
        }

        return $all;
    }

}