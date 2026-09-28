<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cari & Stok API v1 ve webhook entegrasyon dokümantasyonu">
    <title>Cari & Stok — Entegrasyon Dokümantasyonu</title>
    <style>
        :root {
            --navy: #07111f;
            --navy-2: #101b30;
            --blue: #1769ff;
            --blue-soft: #eaf2ff;
            --text: #172033;
            --muted: #61708a;
            --line: #dfe5ef;
            --surface: #ffffff;
            --page: #f4f7fb;
            --green: #079669;
            --green-soft: #ecfdf5;
            --orange: #b45309;
            --red: #dc2626;
            --radius: 16px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            color: var(--text);
            background: var(--page);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.65;
        }
        a { color: inherit; }
        code, pre { font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace; }

        .app-shell { min-height: 100vh; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 0 34px;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(12px);
        }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 230px; }
        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(145deg, #1769ff, #0c4ccc);
            box-shadow: 0 8px 24px rgba(23, 105, 255, .24);
            font-weight: 800;
        }
        .brand-name { font-weight: 800; letter-spacing: -.02em; }
        .brand-subtitle { color: var(--muted); font-size: 12px; }
        .page-heading { flex: 1; }
        .eyebrow {
            color: var(--blue);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .page-heading h1 { margin: 2px 0 0; font-size: 19px; letter-spacing: -.02em; }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 13px;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
            color: #047857;
            background: var(--green-soft);
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; }

        .layout {
            width: min(1500px, calc(100% - 40px));
            margin: 30px auto 70px;
            display: grid;
            grid-template-columns: 270px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }
        .toc {
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 124px);
            overflow-y: auto;
            padding: 18px 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: 0 10px 34px rgba(15, 23, 42, .06);
            scrollbar-width: thin;
        }
        .toc-title {
            margin: 6px 10px 8px;
            color: var(--blue);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        .toc-title:not(:first-child) { margin-top: 20px; padding-top: 17px; border-top: 1px solid #edf0f5; }
        .toc a {
            display: block;
            padding: 8px 11px;
            border-left: 3px solid transparent;
            border-radius: 8px;
            color: #53627a;
            text-decoration: none;
            font-size: 13px;
            font-weight: 650;
        }
        .toc a:hover { color: var(--blue); background: var(--blue-soft); border-left-color: var(--blue); }

        .doc {
            min-width: 0;
            padding: clamp(26px, 4vw, 54px);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: 0 10px 34px rgba(15, 23, 42, .05);
        }
        .hero {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: center;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--line);
        }
        .hero h2 { margin: 0 0 10px; font-size: clamp(28px, 4vw, 42px); line-height: 1.15; letter-spacing: -.045em; }
        .hero p { max-width: 820px; margin: 0; color: var(--muted); font-size: 16px; }
        .version {
            padding: 10px 14px;
            border-radius: 12px;
            color: #1d4ed8;
            background: var(--blue-soft);
            font-size: 13px;
            font-weight: 800;
        }
        section { scroll-margin-top: 96px; padding-top: 34px; }
        section + section { margin-top: 8px; }
        h3 { margin: 0 0 13px; padding-bottom: 10px; border-bottom: 1px solid var(--line); font-size: 23px; letter-spacing: -.025em; }
        h4 { margin: 27px 0 10px; font-size: 17px; }
        p { margin: 9px 0; }
        ul, ol { padding-left: 22px; }
        li + li { margin-top: 6px; }
        .inline-code {
            padding: 2px 6px;
            border-radius: 6px;
            color: #114fca;
            background: #f0f5ff;
            font-family: "SFMono-Regular", Consolas, monospace;
            font-size: .9em;
        }
        .callout {
            margin: 18px 0;
            padding: 16px 18px;
            border: 1px solid #bfdbfe;
            border-left: 4px solid var(--blue);
            border-radius: 10px;
            background: #f7fbff;
        }
        .callout.warning { border-color: #fed7aa; border-left-color: #f97316; background: #fffaf5; }
        .callout strong { display: block; margin-bottom: 3px; }
        .code {
            position: relative;
            margin: 14px 0 18px;
            padding: 20px;
            overflow-x: auto;
            border: 1px solid #1e293b;
            border-radius: 12px;
            color: #e6edf7;
            background: var(--navy-2);
            font-size: 13px;
            line-height: 1.65;
            white-space: pre;
        }
        .code .method { color: #60a5fa; font-weight: 800; }
        .code .key { color: #93c5fd; }
        .code .string { color: #86efac; }
        .method-pill {
            display: inline-flex;
            min-width: 64px;
            justify-content: center;
            margin-right: 7px;
            padding: 3px 8px;
            border-radius: 6px;
            color: #fff;
            font: 800 12px/1.4 "SFMono-Regular", Consolas, monospace;
        }
        .get { background: #2563eb; }
        .post { background: #059669; }
        .patch { background: #d97706; }
        .delete { background: #dc2626; }
        .endpoint-title { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
        .endpoint-title code { color: #174ea6; font-size: 15px; }
        .table-wrap { margin: 15px 0 22px; overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; min-width: 650px; font-size: 13px; }
        th { color: #52637d; background: #f7f9fc; text-align: left; font-size: 11px; letter-spacing: .04em; text-transform: uppercase; }
        th, td { padding: 11px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }
        tr:last-child td { border-bottom: 0; }
        td code { color: #1254cc; background: #f1f5fb; padding: 2px 5px; border-radius: 5px; }
        .required { color: var(--green); font-weight: 800; }
        .optional { color: var(--muted); }
        .flow {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 18px 0;
        }
        .flow div { position: relative; padding: 15px; border: 1px solid var(--line); border-radius: 10px; background: #fafcff; font-size: 13px; font-weight: 700; text-align: center; }
        .flow div:not(:last-child)::after { content: "→"; position: absolute; right: -10px; top: 50%; z-index: 2; transform: translate(50%, -50%); color: var(--blue); }
        .downloads { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        .download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid #bfd3ff;
            border-radius: 9px;
            color: #1249a8;
            background: #f4f8ff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 750;
        }
        .download:hover { background: #e8f0ff; }
        .footer { margin-top: 46px; padding-top: 22px; border-top: 1px solid var(--line); color: var(--muted); font-size: 12px; }

        @media (max-width: 900px) {
            .topbar { height: auto; padding: 14px 18px; flex-wrap: wrap; }
            .brand { min-width: 0; }
            .page-heading { order: 3; flex-basis: 100%; }
            .layout { width: min(100% - 24px, 760px); grid-template-columns: 1fr; margin-top: 16px; }
            .toc { position: static; max-height: none; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .toc-title { grid-column: 1 / -1; }
            .doc { padding: 24px 18px; }
            .hero { grid-template-columns: 1fr; }
            .version { justify-self: start; }
            .flow { grid-template-columns: 1fr 1fr; }
            .flow div::after { display: none; }
        }
        @media (max-width: 520px) {
            .brand-subtitle, .status span:last-child { display: none; }
            .toc { grid-template-columns: 1fr; }
            .flow { grid-template-columns: 1fr; }
            .hero h2 { font-size: 28px; }
            .code { padding: 15px; font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">C&S</div>
            <div>
                <div class="brand-name">Cari & Stok</div>
                <div class="brand-subtitle">Entegrasyon Merkezi</div>
            </div>
        </div>
        <div class="page-heading">
            <div class="eyebrow">Geliştirici Rehberi</div>
            <h1>API v1 & Webhook Dokümantasyonu</h1>
        </div>
        <div class="status"><span class="status-dot"></span><span>Sistem kullanıma hazır</span></div>
    </header>

    <main class="layout">
        <nav class="toc" aria-label="Dokümantasyon menüsü">
            <div class="toc-title">Genel</div>
            <a href="#genel">Genel bakış</a>
            <a href="#kimlik">Kimlik doğrulama</a>
            <a href="#base-url">Base URL</a>
            <a href="#limitler">Limitler ve hatalar</a>

            <div class="toc-title">API Endpoint'leri</div>
            <a href="#auth-endpoints">Auth</a>
            <a href="#contacts">Cariler</a>
            <a href="#products">Ürünler</a>
            <a href="#invoices">Faturalar</a>

            <div class="toc-title">Webhook</div>
            <a href="#webhook-genel">Çalışma mantığı</a>
            <a href="#webhook-register">Endpoint kaydı</a>
            <a href="#webhook-payload">Payload</a>
            <a href="#webhook-signature">İmza doğrulama</a>
            <a href="#webhook-retry">Retry davranışı</a>

            <div class="toc-title">Araçlar</div>
            <a href="#postman">Postman dosyaları</a>
            <a href="#kontrol-listesi">Kontrol listesi</a>
        </nav>

        <article class="doc">
            <div class="hero" id="genel">
                <div>
                    <h2>Cari & Stok Entegrasyon API'si</h2>
                    <p>Bu doküman; dış ERP, e-ticaret, mobil uygulama veya özel yazılımların Cari & Stok sistemindeki cari, ürün ve satış faturası verileriyle güvenli biçimde çalışması ve fatura olaylarını webhook ile alması için hazırlanmıştır.</p>
                </div>
                <div class="version">Sürüm v1</div>
            </div>

            <section>
                <h3>1. Sistem nasıl çalışır?</h3>
                <div class="flow">
                    <div>Yönetici token oluşturur</div>
                    <div>API isteği gönderir</div>
                    <div>Cari & Stok işlemi yapar</div>
                    <div>Webhook ile olay bildirir</div>
                </div>
                <ul>
                    <li>Her token yalnızca bağlı olduğu kullanıcının verilerine erişir.</li>
                    <li>API üzerinden oluşturulan faturalar stok ve kasa hareketlerini tek transaction içinde oluşturur.</li>
                    <li>Webhook gönderimleri ana HTTP isteğini bekletmez; veritabanı kuyruğunda arka planda çalışır.</li>
                    <li>Aynı <span class="inline-code">external_reference</span> ile tekrarlanan aynı fatura isteği ikinci bir fatura oluşturmaz.</li>
                </ul>
            </section>

            <section id="kimlik">
                <h3>2. Kimlik doğrulama</h3>
                <p>API, Laravel Sanctum Bearer token kullanır. Token yalnızca yetkili yönetici tarafından Cari &amp; Stok panelindeki <strong>API Tokenları</strong> ekranından oluşturulur. Normal kullanıcılar ve dış servisler token üretemez. Bütün API isteklerinde aşağıdaki header zorunludur:</p>
                <pre class="code">Authorization: Bearer &lt;api_token&gt;
Accept: application/json
Content-Type: application/json</pre>
                <p>Token yetenekleri:</p>
                <div class="table-wrap"><table>
                    <thead><tr><th>Yetenek</th><th>İzin</th></tr></thead>
                    <tbody>
                    <tr><td><code>api:read</code></td><td>Ürün, cari, fatura, webhook endpoint ve teslimat kayıtlarını okur.</td></tr>
                    <tr><td><code>api:write</code></td><td>Cari, ürün, fatura ve webhook endpoint kayıtlarını oluşturur veya değiştirir.</td></tr>
                    </tbody>
                </table></div>
                <div class="callout warning"><strong>Güvenlik</strong>Tokenı URL içine, kaynak koda, ekran görüntüsüne veya paylaşılan Postman collection dosyasına yazmayın.</div>
            </section>

            <section id="base-url">
                <h3>3. Base URL</h3>
                <pre class="code">https://depo-takip.u02.dehasofteticaret.com/api/v1</pre>
                <p>Tüm endpoint adresleri bu URL'nin sonuna eklenir. Örneğin ürün listesi: <span class="inline-code">GET /products</span>.</p>
            </section>

            <section id="limitler">
                <h3>4. Rate limit ve hata cevapları</h3>
                <p>Kimliği doğrulanmış API istekleri kullanıcı başına dakikada 60 istekle sınırlıdır.</p>
                <div class="table-wrap"><table>
                    <thead><tr><th>HTTP</th><th>Anlamı</th><th>Ne yapılmalı?</th></tr></thead>
                    <tbody>
                    <tr><td><code>200</code></td><td>Başarılı veya idempotent tekrar</td><td>Cevabı işle.</td></tr>
                    <tr><td><code>201</code></td><td>Yeni kayıt oluşturuldu</td><td>Dönen ID'yi sakla.</td></tr>
                    <tr><td><code>202</code></td><td>Arka plan işi kabul edildi</td><td>Teslimat durumunu daha sonra kontrol et.</td></tr>
                    <tr><td><code>401</code></td><td>Token yok/geçersiz</td><td>Tokenı yenile veya kontrol et.</td></tr>
                    <tr><td><code>403</code></td><td>Token yetkisi yetersiz</td><td>Doğru yetenekli token kullan.</td></tr>
                    <tr><td><code>404</code></td><td>Kayıt yok veya kullanıcıya ait değil</td><td>ID ve kullanıcıyı kontrol et.</td></tr>
                    <tr><td><code>409</code></td><td>Durum veya dış referans çakışması</td><td>Aynı referansın içeriğini kontrol et.</td></tr>
                    <tr><td><code>422</code></td><td>Doğrulama hatası</td><td><code>errors</code> alanındaki girdileri düzelt.</td></tr>
                    <tr><td><code>429</code></td><td>Rate limit aşıldı</td><td>Bekleyip tekrar dene.</td></tr>
                    <tr><td><code>500</code></td><td>Beklenmeyen sunucu hatası</td><td>İstek kimliği ve zamanıyla destek ekibine bildir.</td></tr>
                    </tbody>
                </table></div>
            </section>

            <section id="auth-endpoints">
                <h3>5. Token doğrulama endpoint'leri</h3>
                <div class="callout"><strong>Token temini</strong>API tokenı yetkili yönetici tarafından paneldeki <strong>API Tokenları</strong> sayfasında ilgili kullanıcı seçilerek oluşturulur ve entegrasyon firmasına güvenli biçimde iletilir. Güvenlik nedeniyle tokenın açık değeri yalnızca oluşturulduğu anda gösterilir.</div>
                <h4 class="endpoint-title"><span class="method-pill get">GET</span><code>/auth/me</code> — token sahibini getir</h4>
                <h4 class="endpoint-title"><span class="method-pill post">POST</span><code>/auth/logout</code> — kullanılan tokenı iptal et</h4>
            </section>

            <section id="contacts">
                <h3>6. Cari endpoint'leri</h3>
                <h4 class="endpoint-title"><span class="method-pill get">GET</span><code>/contacts?per_page=20</code> — carileri listele</h4>
                <h4 class="endpoint-title"><span class="method-pill post">POST</span><code>/contacts</code> — cari oluştur</h4>
                <div class="table-wrap"><table>
                    <thead><tr><th>Alan</th><th>Tip</th><th>Zorunlu</th><th>Açıklama</th></tr></thead>
                    <tbody>
                    <tr><td><code>name</code></td><td>string</td><td class="required">Evet</td><td>Cari adı, en fazla 255 karakter.</td></tr>
                    <tr><td><code>type</code></td><td>string</td><td class="required">Evet</td><td><code>customer</code> veya <code>supplier</code>.</td></tr>
                    <tr><td><code>phone</code></td><td>string</td><td class="optional">Hayır</td><td>10–20 karakter.</td></tr>
                    <tr><td><code>email</code></td><td>email</td><td class="optional">Hayır</td><td>Geçerli e-posta adresi.</td></tr>
                    <tr><td><code>address</code></td><td>string</td><td class="optional">Hayır</td><td>Açık adres.</td></tr>
                    <tr><td><code>note</code></td><td>string</td><td class="optional">Hayır</td><td>Serbest not.</td></tr>
                    </tbody>
                </table></div>
                <pre class="code">{
  <span class="key">"name"</span>: <span class="string">"ABC Teknoloji"</span>,
  <span class="key">"type"</span>: <span class="string">"customer"</span>,
  <span class="key">"phone"</span>: <span class="string">"05321234567"</span>,
  <span class="key">"email"</span>: <span class="string">"info@abc.test"</span>,
  <span class="key">"address"</span>: <span class="string">"İstanbul"</span>
}</pre>
            </section>

            <section id="products">
                <h3>7. Ürün endpoint'leri</h3>
                <h4 class="endpoint-title"><span class="method-pill get">GET</span><code>/products?per_page=20</code> — ürünleri listele</h4>
                <h4 class="endpoint-title"><span class="method-pill post">POST</span><code>/products</code> — ürün ve açılış stoğu oluştur</h4>
                <div class="table-wrap"><table>
                    <thead><tr><th>Alan</th><th>Tip</th><th>Zorunlu</th><th>Açıklama</th></tr></thead>
                    <tbody>
                    <tr><td><code>name</code></td><td>string</td><td class="required">Evet</td><td>Ürün adı.</td></tr>
                    <tr><td><code>code</code></td><td>string</td><td class="required">Evet</td><td>Kullanıcı içinde benzersiz stok kodu.</td></tr>
                    <tr><td><code>barcode</code></td><td>string</td><td class="optional">Hayır</td><td>8–30 karakter.</td></tr>
                    <tr><td><code>purchase_price</code></td><td>number</td><td class="required">Evet</td><td>Negatif olamaz.</td></tr>
                    <tr><td><code>sale_price</code></td><td>number</td><td class="required">Evet</td><td>Faturalarda güvenilir fiyat kaynağıdır.</td></tr>
                    <tr><td><code>tax_rate</code></td><td>integer</td><td class="required">Evet</td><td>0–100 arası.</td></tr>
                    <tr><td><code>min_stock</code></td><td>integer</td><td class="required">Evet</td><td>Minimum stok uyarı seviyesi.</td></tr>
                    <tr><td><code>opening_stock</code></td><td>integer</td><td class="required">Evet</td><td>Açılış stok miktarı.</td></tr>
                    </tbody>
                </table></div>
                <pre class="code">{
  <span class="key">"name"</span>: <span class="string">"Kablosuz Mouse"</span>,
  <span class="key">"code"</span>: <span class="string">"MOUSE-001"</span>,
  <span class="key">"barcode"</span>: <span class="string">"869000000001"</span>,
  <span class="key">"purchase_price"</span>: 300,
  <span class="key">"sale_price"</span>: 450,
  <span class="key">"tax_rate"</span>: 20,
  <span class="key">"min_stock"</span>: 5,
  <span class="key">"opening_stock"</span>: 50
}</pre>
            </section>

            <section id="invoices">
                <h3>8. Satış faturası endpoint'leri</h3>
                <h4 class="endpoint-title"><span class="method-pill get">GET</span><code>/invoices</code> — faturaları listele</h4>
                <h4 class="endpoint-title"><span class="method-pill get">GET</span><code>/invoices/{id}</code> — fatura detayını getir</h4>
                <h4 class="endpoint-title"><span class="method-pill post">POST</span><code>/invoices</code> — fatura oluştur</h4>
                <pre class="code">{
  <span class="key">"external_reference"</span>: <span class="string">"ERP-SALE-84725"</span>,
  <span class="key">"contact_id"</span>: 4,
  <span class="key">"invoice_number"</span>: <span class="string">"ERP-2026-00001"</span>,
  <span class="key">"items"</span>: [
    { <span class="key">"product_id"</span>: 2, <span class="key">"quantity"</span>: 3 }
  ]
}</pre>
                <div class="callout"><strong>Idempotency</strong>Aynı <code>external_reference</code> ve aynı içerikle tekrar gönderilen istek mevcut faturayı <code>200</code> ile döndürür. Aynı referans farklı içerikle kullanılırsa <code>409 Conflict</code> döner.</div>
                <p>Birim fiyat istemciden alınmaz. Sunucu, ürün kaydındaki güncel <span class="inline-code">sale_price</span> değerini kullanır; stok ve kasa hareketleri transaction içinde oluşturulur.</p>
                <h4 class="endpoint-title"><span class="method-pill patch">PATCH</span><code>/invoices/{id}</code> — fatura numarasını güncelle</h4>
                <pre class="code">{ <span class="key">"invoice_number"</span>: <span class="string">"ERP-2026-00001-R1"</span> }</pre>
                <h4 class="endpoint-title"><span class="method-pill delete">DELETE</span><code>/invoices/{id}</code> — faturayı iptal et</h4>
                <p>Fatura fiziksel olarak silinmez. Stok geri verilir, kasa ters kaydı oluşturulur ve fatura <span class="inline-code">cancelled</span> durumuna geçirilir. Aynı iptal tekrar gönderilirse yeni hareket oluşmaz.</p>
            </section>

            <section id="webhook-genel">
                <h3>9. Webhook çalışma mantığı</h3>
                <div class="flow">
                    <div>Fatura olayı oluşur</div>
                    <div>Payload imzalanır</div>
                    <div>Queue worker gönderir</div>
                    <div>Alıcı 2xx döner</div>
                </div>
                <p>Desteklenen olaylar:</p>
                <ul>
                    <li><span class="inline-code">invoice.created</span> — fatura oluşturuldu.</li>
                    <li><span class="inline-code">invoice.cancelled</span> — fatura iptal edildi.</li>
                    <li><span class="inline-code">webhook.test</span> — yalnızca bağlantı testi.</li>
                </ul>
            </section>

            <section id="webhook-register">
                <h3>10. Webhook endpoint kaydı</h3>
                <h4 class="endpoint-title"><span class="method-pill post">POST</span><code>/webhook-endpoints</code></h4>
                <pre class="code">{
  <span class="key">"url"</span>: <span class="string">"https://entegrasyon.example.com/webhooks/cari-takip"</span>,
  <span class="key">"events"</span>: [<span class="string">"invoice.created"</span>, <span class="string">"invoice.cancelled"</span>]
}</pre>
                <p><code>201 Created</code> cevabındaki <span class="inline-code">secret</span> yalnızca bu cevapta gösterilir. İmza doğrulaması için güvenli biçimde saklanmalıdır.</p>
                <pre class="code">{
  <span class="key">"success"</span>: true,
  <span class="key">"data"</span>: {
    <span class="key">"id"</span>: 3,
    <span class="key">"url"</span>: <span class="string">"https://entegrasyon.example.com/webhooks/cari-takip"</span>,
    <span class="key">"active"</span>: true,
    <span class="key">"secret"</span>: <span class="string">"whsec_..."</span>
  }
}</pre>
                <h4 class="endpoint-title"><span class="method-pill get">GET</span><code>/webhook-endpoints</code> — endpoint'leri listele</h4>
                <h4 class="endpoint-title"><span class="method-pill patch">PATCH</span><code>/webhook-endpoints/{id}</code> — URL, olay veya aktiflik güncelle</h4>
                <h4 class="endpoint-title"><span class="method-pill delete">DELETE</span><code>/webhook-endpoints/{id}</code> — endpoint'i pasife al</h4>
                <h4 class="endpoint-title"><span class="method-pill post">POST</span><code>/webhook-endpoints/{id}/test</code> — test olayını kuyruğa ekle</h4>
                <p>Test isteği <code>202 Accepted</code> döner; gerçek gönderim queue worker tarafından arka planda yapılır.</p>
                <h4 class="endpoint-title"><span class="method-pill get">GET</span><code>/webhook-deliveries</code> — teslimat geçmişini listele</h4>
            </section>

            <section id="webhook-payload">
                <h3>11. Webhook payload örneği</h3>
                <pre class="code">{
  <span class="key">"id"</span>: <span class="string">"3efec29a-1dc9-4b46-b767-cba7d3631833"</span>,
  <span class="key">"type"</span>: <span class="string">"invoice.created"</span>,
  <span class="key">"api_version"</span>: <span class="string">"v1"</span>,
  <span class="key">"occurred_at"</span>: <span class="string">"2026-09-22T15:30:00Z"</span>,
  <span class="key">"data"</span>: {
    <span class="key">"invoice"</span>: {
      <span class="key">"id"</span>: 15,
      <span class="key">"invoice_number"</span>: <span class="string">"FAT-2026-001"</span>,
      <span class="key">"external_reference"</span>: <span class="string">"ERP-1001"</span>,
      <span class="key">"contact_id"</span>: 4,
      <span class="key">"total_amount"</span>: <span class="string">"1350.00"</span>,
      <span class="key">"status"</span>: <span class="string">"active"</span>,
      <span class="key">"created_at"</span>: <span class="string">"2026-09-22T15:30:00Z"</span>,
      <span class="key">"cancelled_at"</span>: null
    }
  }
}</pre>
            </section>

            <section id="webhook-signature">
                <h3>12. Webhook imza doğrulaması</h3>
                <p>Her webhook isteğinde şu headerlar bulunur:</p>
                <pre class="code">X-Webhook-Id: 3efec29a-1dc9-4b46-b767-cba7d3631833
X-Webhook-Timestamp: 1789983000
X-Webhook-Signature: v1=&lt;hex-hmac&gt;</pre>
                <p>İmzalanan metin:</p>
                <pre class="code">&lt;timestamp&gt;.&lt;ham JSON body&gt;</pre>
                <p>PHP doğrulama örneği:</p>
                <pre class="code">$rawBody = file_get_contents('php://input');
$timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '';
$received = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$expected = 'v1='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

if (!hash_equals($expected, $received)) {
    http_response_code(401);
    exit;
}</pre>
                <div class="callout warning"><strong>Ham body zorunludur</strong>JSON'u decode edip yeniden encode ederek imza hesaplamayın. Boşluk veya alan sırası değişirse imza eşleşmez. Timestamp için örneğin 5 dakikalık tolerans ve event ID için idempotency kaydı uygulayın.</div>
            </section>

            <section id="webhook-retry">
                <h3>13. Teslimat ve retry davranışı</h3>
                <div class="table-wrap"><table>
                    <thead><tr><th>Alıcı cevabı</th><th>Davranış</th></tr></thead>
                    <tbody>
                    <tr><td><code>2xx</code></td><td>Teslim edildi olarak işaretlenir.</td></tr>
                    <tr><td><code>408, 425, 429</code></td><td>Geçici hata kabul edilir ve tekrar denenir.</td></tr>
                    <tr><td><code>5xx</code> veya bağlantı hatası</td><td>Geçici hata kabul edilir ve tekrar denenir.</td></tr>
                    <tr><td>Diğer <code>4xx</code></td><td>Kalıcı hata kabul edilir.</td></tr>
                    </tbody>
                </table></div>
                <p>En fazla 5 deneme yapılır. Bekleme planı yaklaşık 1 dakika, 5 dakika, 15 dakika ve 1 saattir. Aynı olayın bütün denemelerinde event ID değişmez.</p>
            </section>

            <section id="postman">
                <h3>14. Postman ve indirilebilir dosyalar</h3>
                <p>Bu dosyalar yalnızca isteğe bağlı hızlı test araçlarıdır; entegrasyon için zorunlu değildir. Postman kullanacaksanız collection ve environment dosyalarını içe aktararak panel yöneticisinin verdiği tokenı environment içindeki <code>token</code> alanına girin.</p>
                <div class="downloads">
                    <a class="download" href="/downloads/Cari-Takip-API-v1.postman_collection.json" download>↓ Postman Collection</a>
                    <a class="download" href="/downloads/Production-Test.postman_environment.json" download>↓ Production Environment</a>
                    <a class="download" href="/downloads/api-v1.md" download>↓ API Markdown</a>
                    <a class="download" href="/downloads/webhooks-v1.md" download>↓ Webhook Markdown</a>
                </div>
            </section>

            <section id="kontrol-listesi">
                <h3>15. Entegrasyon kontrol listesi</h3>
                <ol>
                    <li>Ayrı bir test kullanıcısı ve API tokenı oluşturun.</li>
                    <li><code>GET /auth/me</code> ile tokenı doğrulayın.</li>
                    <li>Test carisi ve ürünü oluşturun.</li>
                    <li>Herkese açık HTTPS webhook adresinizi kaydedin ve secretı saklayın.</li>
                    <li>Test webhooku gönderip HMAC doğrulamasını tamamlayın.</li>
                    <li>Fatura oluşturup <code>invoice.created</code> olayını doğrulayın.</li>
                    <li>Faturayı iptal edip <code>invoice.cancelled</code> olayını doğrulayın.</li>
                    <li>Aynı event ID'nin iki kez işlenmediğini kontrol edin.</li>
                </ol>
            </section>

            <div class="footer">Cari & Stok API v1 · Son güncelleme: 22.09.2026 · Production entegrasyon rehberi</div>
        </article>
    </main>
</div>
</body>
</html>
