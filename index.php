<?php
// 載入站台設定
$siteConfigFile = __DIR__ . '/data/site-config.json';
$defaultConfig = [
    'site' => [
        'title' => '美國保健品代購｜正品保證・快速送達台灣',
        'description' => '美國保健品代購｜正品保證・快速送達台灣。維他命C、魚油、膠原蛋白、益生菌等。',
        'keywords' => '美國保健品代購,正品保證,快速送達台灣,維他命C,魚油,膠原蛋白,益生菌',
        'url' => '',
        'ogImage' => ''
    ],
    'brand' => [
        'text' => 'HealthShop 代購',
        'mark' => 'HS'
    ],
    'contact' => [
        'lineId' => '@yourlineid',
        'email' => 'service@yourbrand.tw'
    ]
];

if (file_exists($siteConfigFile)) {
    $jsonContent = file_get_contents($siteConfigFile);
    $siteConfig = json_decode($jsonContent, true);
    if (!is_array($siteConfig)) {
        $siteConfig = $defaultConfig;
    }
} else {
    $siteConfig = $defaultConfig;
}

// 提取設定值
$siteTitle = htmlspecialchars($siteConfig['site']['title'], ENT_QUOTES, 'UTF-8');
$brandText = htmlspecialchars($siteConfig['brand']['text'], ENT_QUOTES, 'UTF-8');
$brandMark = htmlspecialchars($siteConfig['brand']['mark'], ENT_QUOTES, 'UTF-8');
$lineIdRaw = (string)($siteConfig['contact']['lineId']);
$emailRaw = (string)($siteConfig['contact']['email']);
$lineUrl = 'https://line.me/ti/p/' . rawurlencode($lineIdRaw);
$emailHref = 'mailto:' . $emailRaw . '?subject=' . rawurlencode('保健品代購詢問');
$seoDesc = htmlspecialchars($siteConfig['site']['description'], ENT_QUOTES, 'UTF-8');
$seoKeywords = htmlspecialchars($siteConfig['site']['keywords'], ENT_QUOTES, 'UTF-8');
$siteUrl = rtrim((string)($siteConfig['site']['url']), '/');
$ogImage = (string)($siteConfig['site']['ogImage']);
$canonical = $siteUrl ? ($siteUrl . '/index.php') : '';
?>
<!doctype html>
<html lang="zh-Hant-TW">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="description" content="<?php echo $seoDesc; ?>" />
    <meta name="keywords" content="<?php echo $seoKeywords; ?>" />
    <?php if($canonical){ ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php } ?>
    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <?php if($siteUrl){ ?><meta property="og:url" content="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>" /><?php } ?>
    <meta property="og:title" content="<?php echo $siteTitle; ?>" />
    <meta property="og:description" content="<?php echo $seoDesc; ?>" />
    <?php if($ogImage){ ?><meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>" /><?php } ?>
    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo $siteTitle; ?>" />
    <meta name="twitter:description" content="<?php echo $seoDesc; ?>" />
    <?php if($ogImage){ ?><meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>" /><?php } ?>
    <!-- JSON-LD -->
    <script type="application/ld+json">
    <?php echo json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => $brandText,
      'url' => $siteUrl ?: null,
      'description' => $seoDesc,
      'contactPoint' => [
        [
          '@type' => 'ContactPoint',
          'contactType' => 'customer support',
          'email' => $emailRaw,
        ]
      ]
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); ?>
    </script>
    <title><?php echo $siteTitle; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="./style.css" />
  </head>
  <body>
    <header class="site-header">
      <div class="container header-inner">
        <a href="#" class="logo" aria-label="首頁">
          <span class="logo-mark"><?php echo $brandMark; ?></span>
          <span class="logo-text" id="brandText"><?php echo $brandText; ?></span>
        </a>
        <nav class="nav">
          <a href="#products">熱銷商品</a>
          <a href="#how">流程說明</a>
          <a href="#faq">常見問題</a>
          <a href="#contact">聯絡我們</a>
        </nav>
        <div class="header-actions">
          <button class="cart-toggle" aria-controls="inquiryDrawer" aria-expanded="false">
            詢問清單 <span class="cart-count" id="cartCount">0</span>
          </button>
          <button class="admin-toggle" aria-controls="adminDrawer" aria-expanded="false">商品管理</button>
        </div>
      </div>
    </header>

    <main>
      <!-- Hero Section -->
      <section class="hero">
        <div class="container hero-inner">
          <div class="hero-content">
            <h1>美國保健品代購</h1>
            <p class="subtitle">正品保證・快速送達台灣</p>
            <div class="hero-ctas">
              <a href="#contact" class="btn btn-primary">立即下單</a>
              <a id="lineLink" href="<?php echo htmlspecialchars($lineUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-outline">加入LINE洽詢</a>
            </div>
            <p class="hero-note">支援多品牌代購：維他命C、魚油、膠原蛋白、益生菌等</p>
          </div>
          <div class="hero-art" aria-hidden="true">
            <div class="pill pill-a"></div>
            <div class="pill pill-b"></div>
            <div class="pill pill-c"></div>
          </div>
        </div>
      </section>

      <!-- Featured Products -->
      <section class="section" id="products">
        <div class="container">
          <h2 class="section-title">熱銷推薦</h2>
          <p class="section-subtitle">精選美國熱賣保健品，支援客製代購與組合詢價</p>
          <div class="product-grid">
            <?php
            // 檢查 JSON 檔案是否存在
            $productsFile = __DIR__ . '/data/products.json';
            if (file_exists($productsFile)) {
              $jsonContent = file_get_contents($productsFile);
              $products = json_decode($jsonContent, true);
              
              if (is_array($products)) {
                foreach ($products as $product) {
                  $imgHtml = '';
                  if (!empty($product['img'])) {
                    $imgHtml = '<img src="' . htmlspecialchars($product['img'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . ' 示意圖" style="width: 100%; height: 100%; object-fit: cover;">';
                  } else {
                    // 使用預設樣式
                    $imgHtml = '<div class="default-product-img ' . htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') . '" aria-label="' . htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . ' 預設圖示">' . getProductIcon($product['id']) . '</div>';
                  }
                  
                  echo '<article class="product-card" data-id="' . htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') . '" data-name="' . htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . '">';
                  echo '<div class="product-image-wrap">' . $imgHtml . '</div>';
                  echo '<div class="product-info">';
                  echo '<h3>' . htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . '</h3>';
                  echo '<p>' . htmlspecialchars($product['desc'], ENT_QUOTES, 'UTF-8') . '</p>';
                  echo '<button class="btn btn-add add-to-inquiry">加入詢問清單</button>';
                  echo '</div>';
                  echo '</article>';
                }
              } else {
                echo '<!-- JSON 解析失敗，使用 JS 渲染 -->';
              }
            } else {
              echo '<!-- JSON 檔案不存在，使用 JS 渲染 -->';
            }
            
            // 輔助函數：取得產品圖示
            function getProductIcon($productId) {
              $productIcons = [
                'vitamin-c' => 'VC',
                'fish-oil' => 'Ω3',
                'collagen' => '膠原',
                'probiotics' => '益生',
                'multi-vitamin' => 'MV',
                'vitamin-d' => 'VD'
              ];
              return $productIcons[$productId] ?? '?';
            }
            ?>
          </div>
        </div>
      </section>

      <!-- How It Works -->
      <section class="section section-alt" id="how">
        <div class="container">
          <h2 class="section-title">代購流程</h2>
          <div class="steps">
            <div class="step">
              <div class="step-icon" aria-hidden="true">📝</div>
              <h3>下單</h3>
              <p>選擇商品並加入詢問清單，填寫聯絡方式送出。</p>
            </div>
            <div class="step">
              <div class="step-icon" aria-hidden="true">✈️</div>
              <h3>代購</h3>
              <p>我們於美國採購正品並安排空運或集運。</p>
            </div>
            <div class="step">
              <div class="step-icon" aria-hidden="true">📦</div>
              <h3>收貨</h3>
              <p>完成清關後寄送至台灣地址，提供物流追蹤。</p>
            </div>
          </div>
        </div>
      </section>

      <!-- FAQ -->
      <section class="section" id="faq">
        <div class="container">
          <h2 class="section-title">常見問題</h2>
          <div class="faq">
            <details>
              <summary>運送時間需要多久？</summary>
              <p>一般狀況下約 7-14 個工作天（不含假日），旺季與通關查驗可能延長。</p>
            </details>
            <details>
              <summary>如何付款？</summary>
              <p>提供台灣銀行轉帳或行動支付。確認商品與金額後再行付款。</p>
            </details>
            <details>
              <summary>是否會被課稅？需要提供什麼資料？</summary>
              <p>依台灣海關規定可能課徵進口稅。若需報關可能請您提供身分證字號作實名認證。</p>
            </details>
            <details>
              <summary>是否保證正品？</summary>
              <p>所有商品均自美國正規通路採購並保留單據，保障您的權益。</p>
            </details>
          </div>
        </div>
      </section>

      <!-- Contact -->
      <section class="section section-alt" id="contact">
        <div class="container">
          <h2 class="section-title">聯絡我們</h2>
          <div class="contact-grid">
            <div class="contact-cards">
              <div class="contact-card line">
                <span class="contact-icon">💬</span>
                <div>
                  <strong>加入 LINE 洽詢</strong>
                  <div class="muted" id="lineIdText"><?php echo htmlspecialchars($lineIdRaw, ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="contact-actions">
                    <button class="btn btn-sm btn-outline copy-btn" data-copy="<?php echo htmlspecialchars($lineIdRaw, ENT_QUOTES, 'UTF-8'); ?>" data-type="line">複製 ID</button>
                    <a id="lineCardLink" href="<?php echo htmlspecialchars($lineUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary">加入好友</a>
                  </div>
                </div>
              </div>
              <div class="contact-card email">
                <span class="contact-icon">✉️</span>
                <div>
                  <strong>Email</strong>
                  <div class="muted" id="emailText"><?php echo htmlspecialchars($emailRaw, ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="contact-actions">
                    <button class="btn btn-sm btn-outline copy-btn" data-copy="<?php echo htmlspecialchars($emailRaw, ENT_QUOTES, 'UTF-8'); ?>" data-type="email">複製信箱</button>
                    <!--<a id="emailLink" href="<?php echo htmlspecialchars($emailHref, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-primary">發送郵件</a>-->
                  </div>
                </div>
              </div>
            </div>

            <form id="contactForm" class="contact-form" autocomplete="on">
              <div class="form-row">
                <div class="form-field">
                  <label for="name">姓名</label>
                  <input type="text" id="name" name="name" placeholder="您的大名" required />
                </div>
                <div class="form-field">
                  <label for="email">Email</label>
                  <input type="email" id="email" name="email" placeholder="example@mail.com" required />
                </div>
              </div>
              <div class="form-row">
                <div class="form-field">
                  <label for="lineId">LINE ID（選填）</label>
                  <input type="text" id="lineId" name="lineId" placeholder="@yourlineid" />
                </div>
                <div class="form-field">
                  <label for="phone">手機（選填）</label>
                  <input type="tel" id="phone" name="phone" inputmode="numeric" placeholder="09xx-xxx-xxx" />
                </div>
              </div>
              <div class="form-field">
                <label for="message">想詢問的內容</label>
                <textarea id="message" name="message" rows="4" placeholder="請簡述想購買的商品或問題"></textarea>
              </div>
              <input type="hidden" id="inquiryPayload" name="inquiry" />
              <div class="form-actions">
                <button type="submit" class="btn btn-primary">送出詢問</button>
                <button type="button" class="btn btn-outline" id="openCart">查看詢問清單</button>
              </div>
              <!--<p class="form-note">按下送出後，系統會自動開啟 Email 草稿，內含您的詢問清單。</p>-->
            </form>
          </div>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="container footer-inner">
        <div>
          <div class="logo footer-logo"><span class="logo-mark"><?php echo $brandMark; ?></span><span class="logo-text" id="brandTextFooter"><?php echo $brandText; ?></span></div>
          <p class="muted">© <span id="year"></span> HealthShop. All rights reserved.</p>
        </div>
        <div class="disclaimer">
          <strong>非醫療聲明：</strong>
          本網站所述商品為一般營養補充品，非醫療或治療用途。實際效果因人而異，如有身體不適請諮詢專業醫師。
        </div>
      </div>
    </footer>

    <!-- Inquiry Drawer -->
    <div class="drawer-overlay" id="drawerOverlay" hidden></div>
    <aside class="drawer" id="inquiryDrawer" aria-hidden="true">
      <div class="drawer-header">
        <h3>詢問清單</h3>
        <button class="icon-btn" id="closeDrawer" aria-label="關閉詢問清單">✕</button>
      </div>
      <div class="drawer-body">
        <ul class="inquiry-list" id="inquiryList">
          <!-- 由 JS 動態插入 -->
        </ul>
        <div class="empty-hint" id="emptyHint">尚未加入任何商品</div>
      </div>
      <div class="drawer-footer">
        <button class="btn btn-outline" id="clearInquiry">清空</button>
        <button class="btn btn-primary" id="toContact">提交詢問</button>
      </div>
    </aside>

    <!-- Admin Drawer -->
    <div class="drawer-overlay" id="adminOverlay" hidden></div>
    <aside class="drawer" id="adminDrawer" aria-hidden="true">
      <div class="drawer-header">
        <h3>商品管理</h3>
        <div class="drawer-head-actions">
          <button class="btn btn-outline" id="copyAdminLink">複製管理連結</button>
          <button class="btn btn-danger" id="logoutAdmin">登出</button>
          <button class="icon-btn" id="closeAdmin" aria-label="關閉商品管理">✕</button>
        </div>
      </div>
      <div class="drawer-body">
        <div class="admin-actions">
          <button class="btn btn-outline" id="addProductBtn">新增商品</button>
          <button class="btn btn-outline" id="reloadProducts">重新載入商品</button>
          <button class="btn btn-outline" id="editSiteConfig">編輯站台設定</button>
          <button class="btn btn-danger" id="resetDefaults">重置為預設商品</button>
        </div>
        <ul class="product-admin-list" id="productAdminList"></ul>
        <hr class="admin-sep" />
        
        <!-- 站台設定編輯表單 -->
        <div id="siteConfigForm" class="admin-form" style="display: none;">
          <h4>站台設定</h4>
          <div class="form-field">
            <label for="siteTitle">網站標題</label>
            <input type="text" id="siteTitle" required />
          </div>
          <div class="form-field">
            <label for="brandText">品牌名稱</label>
            <input type="text" id="brandText" required />
          </div>
          <div class="form-field">
            <label for="brandMark">品牌標記</label>
            <input type="text" id="brandMark" required />
          </div>
          <div class="form-field">
            <label for="lineId">LINE ID</label>
            <input type="text" id="lineId" required />
          </div>
          <div class="form-field">
            <label for="email">聯絡信箱</label>
            <input type="email" id="email" required />
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-primary" id="saveSiteConfig">儲存設定</button>
            <button type="button" class="btn btn-outline" id="resetSiteConfig">重置設定</button>
            <button type="button" class="btn btn-outline" id="closeSiteConfig">關閉</button>
          </div>
        </div>
        
        <hr class="admin-sep" />
        <form id="productForm" class="admin-form" autocomplete="off">
          <input type="hidden" id="editingId" />
          <div class="form-field">
            <label for="prodName">商品名稱</label>
            <input type="text" id="prodName" required placeholder="例如：維他命C 1000mg" />
          </div>
          <div class="form-field">
            <label for="prodDesc">商品描述</label>
            <textarea id="prodDesc" rows="3" placeholder="簡短特色與用途"></textarea>
          </div>
          <div class="form-field">
            <label for="prodImg">圖片網址</label>
            <input type="text" id="prodImg" placeholder="/uploads/xxx.jpg 或 https://..." />
          </div>
          <div class="form-field">
            <label for="prodFile">或上傳圖片（JPG/PNG/WebP/GIF）</label>
            <input type="file" id="prodFile" accept="image/jpeg,image/png,image/webp,image/gif" />
            <div class="muted">上傳後會自動帶入圖片網址</div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">儲存</button>
            <button type="button" class="btn btn-outline" id="cancelEdit">取消</button>
          </div>
          <p class="form-note">提示：未填圖片時，會自動使用預設示意圖。</p>
        </form>
      </div>
    </aside>

    <!-- Floating Cart Button (mobile) -->
    <button class="fab" id="fabCart" aria-label="開啟詢問清單">
      🧾 <span class="badge" id="badgeCount">0</span>
    </button>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="./script.js"></script>
  </body>
</html>
