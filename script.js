(function($){
  const STORAGE_KEY = 'inquiryItems.v1';
  const PRODUCT_KEY = 'products.v1';
  const ADMIN_KEY = 'adminToken.v1';

  let cfg = { siteTitle:'', brandText:'', lineId:'', email:'' };

  const $drawer = $('#inquiryDrawer');
  const $overlay = $('#drawerOverlay');
  const $list = $('#inquiryList');
  const $empty = $('#emptyHint');
  const $count = $('#cartCount');
  const $badge = $('#badgeCount');
  const $payload = $('#inquiryPayload');

  const $productGrid = $('.product-grid');

  const $adminToggle = $('.admin-toggle');
  const $adminDrawer = $('#adminDrawer');
  const $adminOverlay = $('#adminOverlay');
  const $adminList = $('#productAdminList');
  const $productForm = $('#productForm');
  const $editingId = $('#editingId');
  const $prodName = $('#prodName');
  const $prodDesc = $('#prodDesc');
  const $prodImg = $('#prodImg');
  const $prodFile = $('#prodFile');

  const DEFAULT_IMAGE = 'https://source.unsplash.com/600x400/?supplement';
  const DEFAULT_PRODUCTS = [
    { id:'vitamin-c', name:'維他命C 1000mg', desc:'高劑量每日補給，增強體力與精神，常備保健首選。', img:'https://picsum.photos/600/400?random=1' },
    { id:'fish-oil', name:'高濃度魚油', desc:'Omega-3 含量高，支持心血管健康與日常保養。', img:'https://picsum.photos/600/400?random=2' },
    { id:'collagen', name:'膠原蛋白粉', desc:'美妍養護，添加維生素C 配方，沖泡方便好入口。', img:'https://picsum.photos/600/400?random=3' },
    { id:'probiotics', name:'益生菌複方', desc:'多菌株高含量，幫助調整體質，維持消化道機能。', img:'https://picsum.photos/600/400?random=4' },
    { id:'multi-vitamin', name:'綜合維他命', desc:'全方位補給日常所需營養素，簡單一次到位。', img:'https://picsum.photos/600/400?random=5' },
    { id:'vitamin-d', name:'維他命D3 2000 IU', desc:'居家必備好朋友，幫助鈣質吸收與免疫防護。', img:'https://picsum.photos/600/400?random=6' }
  ];

  // ====== Server helpers ======
  async function api(path, opts){
    const res = await fetch(path, Object.assign({
      method:'GET', headers:{'Content-Type':'application/json'}
    }, opts||{}));
    if(!res.ok){ throw new Error('API_ERROR'); }
    return await res.json();
  }
  async function fetchStatus(){ const d = await api('./auth-status.php'); return d; }
  async function login(token){ const d = await api('./auth.php', { method:'POST', body: JSON.stringify({ token }) }); return d; }
  async function logout(){ const d = await api('./logout.php', { method:'POST' }); return d; }

  // ====== Admin session gating ======
  function isAuthedLocal(){ return localStorage.getItem(ADMIN_KEY) === '1'; }
  function setAuthedLocal(v){ if(v){ localStorage.setItem(ADMIN_KEY, '1'); } else { localStorage.removeItem(ADMIN_KEY); } syncAdminVisibility(); }
  function syncAdminVisibility(){ $adminToggle.toggle(isAuthedLocal()); }

  async function ensureStatus(){
    try{
      const s = await fetchStatus();
      cfg = s.config || cfg;
      applyConfigToUI();
      setAuthedLocal(!!s.isAdmin);
    }catch(e){ /* ignore */ }
  }

  // Copy admin link / logout
  $('#copyAdminLink').on('click', async function(){
    const base = window.location.origin + window.location.pathname;
    const token = window.prompt('請輸入要分享的 token（伺服器端會驗證）');
    if(!token){ return; }
    const link = `${base}`; // 不再透過 URL 帶 token
    try{ await navigator.clipboard.writeText(link); toast('已複製站台連結'); }catch{ toast('複製失敗'); }
    try{ const r = await login(token); if(r && r.ok){ setAuthedLocal(true); toast('已啟用管理模式'); } }catch{ toast('驗證失敗'); }
  });
  $('#logoutAdmin').on('click', async function(){ try{ await logout(); }catch(e){} setAuthedLocal(false); toast('已登出管理模式'); closeAdmin(); });

  // 快捷鍵輸入 token：Shift + A + L（先按住 Shift，快速按 A 再按 L）
  let __lastShiftAAt = 0;
  $(document).on('keydown', async function(e){
    const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
    const isTyping = tag === 'input' || tag === 'textarea' || tag === 'select' || (e.target && e.target.isContentEditable);
    if(isTyping) return;
    const key = (e.key || '').toLowerCase();
    const now = Date.now();
    if(e.shiftKey && key === 'a'){
      __lastShiftAAt = now;
      return;
    }
    const within = now - __lastShiftAAt;
    if(e.shiftKey && key === 'l' && within >= 0 && within <= 800){
      __lastShiftAAt = 0;
      e.preventDefault();
      const input = window.prompt('請輸入管理 Token：');
      if(!input) return;
      try{
        const r = await login(input);
        if(r && r.ok){ setAuthedLocal(true); toast('已啟用管理模式'); }
        else{ toast('Token 錯誤'); }
      }catch{ toast('驗證失敗'); }
    }
  });

  // ====== Inquiry list utilities ======
  function loadItems(){ try{ return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }catch(e){ return []; } }
  function saveItems(items){ localStorage.setItem(STORAGE_KEY, JSON.stringify(items)); }
  function getItems(){ return loadItems(); }
  function setItems(items){ saveItems(items); refreshUI(); }
  function addItem(item){ const items = getItems(); if(!items.some(x=>x.id===item.id)){ items.push(item); setItems(items); toast('已加入詢問清單'); } else { toast('已在清單中'); } }
  function removeItem(id){ const items = getItems().filter(x => x.id !== id); setItems(items); }
  function clearItems(){ setItems([]); }

  // ====== Product utilities ======
  let cachedProducts = [];
  
  async function loadProducts(){
    try{
      // 加入時間戳防止快取
      const timestamp = Date.now();
      const response = await fetch(`./products-public.php?t=${timestamp}`);
      if(!response.ok) throw new Error('API_ERROR');
      const data = await response.json();
      if(data.ok && Array.isArray(data.products)){
        cachedProducts = data.products;
        console.log('成功載入商品資料:', data.products);
        return data.products;
      }
      throw new Error('INVALID_RESPONSE');
    }catch(e){
      console.warn('Failed to load products from API, using defaults:', e);
      return DEFAULT_PRODUCTS.slice();
    }
  }
  
  async function saveProducts(items){
    try{
      const response = await fetch('./products.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(items)
      });
      if(!response.ok) throw new Error('SAVE_FAILED');
      const data = await response.json();
      if(data.ok){
        cachedProducts = items;
        return true;
      }
      throw new Error(data.error || 'SAVE_FAILED');
    }catch(e){
      console.error('Failed to save products:', e);
      return false;
    }
  }
  
  async function getProducts(){
    // 強制重新載入，不使用快取
    await loadProducts();
    return cachedProducts;
  }
  
  async function setProducts(items){
    const success = await saveProducts(items);
    if(success){
      renderProducts();
      renderAdminList();
    } else {
      toast('儲存失敗，請重試');
    }
  }
  
  async function resetProducts(){
    if(!isAuthedLocal()){ toast('需管理權限'); return; }
    try{
      const response = await fetch('./products.php', { method: 'PUT' });
      if(!response.ok) throw new Error('RESET_FAILED');
      const data = await response.json();
      if(data.ok){
        cachedProducts = data.products;
        renderProducts();
        renderAdminList();
        toast('已重置為預設商品');
      } else {
        throw new Error(data.error || 'RESET_FAILED');
      }
    }catch(e){
      console.error('Failed to reset products:', e);
      toast('重置失敗，請重試');
    }
  }

  function resolveImageSrc(path){
    if(!path) return '';
    const s = String(path).trim();
    if(/^https?:\/\//i.test(s)) return s; // absolute URL
    if(s.startsWith('/')) return s;        // site-root relative
    return `./${s.replace(/^\.?\/+/, '')}`; // relative to current
  }
  
  // 產品圖示系統
  function getProductIcon(productId){
    const productIcons = {
      'vitamin-c': 'VC',
      'fish-oil': 'Ω3',
      'collagen': '膠原',
      'probiotics': '益生',
      'multi-vitamin': 'MV',
      'vitamin-d': 'VD'
    };
    return productIcons[productId] || '?';
  }

  async function renderProducts(){
    const products = await getProducts();
    console.log('渲染商品，資料:', products); // 除錯用
    $productGrid.empty();
    products.forEach(function(p){
      console.log('處理商品:', p.name, '圖片路徑:', p.img); // 除錯用
      const $card = $('<article/>', { class:'product-card', 'data-id':p.id, 'data-name':p.name });
      const $imgWrap = $('<div/>', { class:'product-image-wrap' });
      const hasImg = !!(p.img && String(p.img).trim() !== '');
      console.log('商品', p.name, '是否有圖片:', hasImg); // 除錯用
      
      if(hasImg && p.img.trim() !== ''){
        const src = resolveImageSrc(p.img);
        console.log('商品', p.name, '解析後圖片路徑:', src); // 除錯用
        
        // 直接使用 IMG 標籤，簡單直接
        const $img = $('<img/>', { 
          src: src,
          alt: `${p.name} 示意圖`,
          style: 'width: 100%; height: 100%; object-fit: cover;'
        });
        
        // 圖片載入成功
        $img.on('load', function(){
          console.log('圖片載入成功:', src);
        });
        
        // 圖片載入失敗 - 使用預設圖片
        $img.on('error', function(){
          console.error('圖片載入失敗:', src);
          $(this).remove();
          
          // 使用預設圖片
          const $defaultImg = $('<div/>', { 
            class: `default-product-img ${p.id}`,
            'aria-label': `${p.name} 預設圖示`
          });
          const iconText = getProductIcon(p.id);
          $defaultImg.text(iconText);
          $imgWrap.append($defaultImg);
          console.log('使用預設圖片:', p.id);
        });
        
        $imgWrap.append($img);
      }else{
        console.log('商品', p.name, '使用預設示意圖');
        
        // 使用預設圖片
        const $defaultImg = $('<div/>', { 
          class: `default-product-img ${p.id}`,
          'aria-label': `${p.name} 預設圖示`
        });
        const iconText = getProductIcon(p.id);
        $defaultImg.text(iconText);
        $imgWrap.append($defaultImg);
        console.log('使用預設圖片:', p.id);
      }
      
      const $info = $('<div/>', { class:'product-info' });
      const $h3 = $('<h3/>', { text: p.name });
      const $p = $('<p/>', { text: p.desc || '' });
      const $btn = $('<button/>', { class:'btn btn-add add-to-inquiry', text:'加入詢問清單' });
      $info.append($h3,$p,$btn);
      $card.append($imgWrap,$info);
      $productGrid.append($card);
    });
  }

  // ====== Admin UI ======
  function openAdmin(){ if(!isAuthedLocal()){ toast('需管理權限'); return; } renderAdminList(); $adminDrawer.addClass('open').attr('aria-hidden','false'); $adminOverlay.removeAttr('hidden'); }
  function closeAdmin(){ $adminDrawer.removeClass('open').attr('aria-hidden','true'); $adminOverlay.attr('hidden', true); clearForm(); }
  async function renderAdminList(){ const products = await getProducts(); $adminList.empty(); products.forEach(function(p){ const $li = $('<li/>', { class:'product-admin-item' }); const $title = $('<div/>', { class:'title', text: p.name }); const $actions = $('<div/>', { class:'admin-item-actions' }); const $edit = $('<button/>', { class:'btn btn-outline', text:'編輯' }).data('id', p.id); const $del = $('<button/>', { class:'btn btn-danger', text:'刪除' }).data('id', p.id); $actions.append($edit,$del); $li.append($title,$actions); $adminList.append($li); }); }
  function clearForm(){ $editingId.val(''); $prodName.val(''); $prodDesc.val(''); $prodImg.val(''); }
  function fillForm(p){ $editingId.val(p.id); $prodName.val(p.name); $prodDesc.val(p.desc || ''); $prodImg.val(p.img || ''); }
  async function upsertProduct(data){ if(!isAuthedLocal()){ toast('需管理權限'); return; } const items = await getProducts(); const idx = items.findIndex(x => x.id === data.id); if(idx >= 0){ items[idx] = data; } else { items.push(data); } await setProducts(items); toast('已儲存商品'); }
  async function deleteProduct(id){ if(!isAuthedLocal()){ toast('需管理權限'); return; } let items = await getProducts(); items = items.filter(x => x.id !== id); await setProducts(items); toast('已刪除商品'); }

  // ====== Inquiry drawer UI ======
  function renderList(){ const items = getItems(); $list.empty(); items.forEach(function(it){ const $li = $('<li/>', { class: 'inquiry-item' }); const $title = $('<div/>', { class: 'item-title', text: it.name }); const $rm = $('<button/>', { class: 'remove-btn', text: '移除' }).data('id', it.id); $li.append($title).append($rm); $list.append($li); }); $empty.toggle(items.length === 0); }
  function updateCounts(){ const n = getItems().length; $count.text(n); $badge.text(n); }
  function updatePayload(){ const items = getItems(); const lines = items.map((x,i)=>`${i+1}. ${x.name}`); $payload.val(lines.join('\n')); }
  function refreshUI(){ renderList(); updateCounts(); updatePayload(); }

  function openDrawer(){ refreshUI(); $drawer.addClass('open').attr('aria-hidden','false'); $overlay.removeAttr('hidden'); }
  function closeDrawer(){ $drawer.removeClass('open').attr('aria-hidden','true'); $overlay.attr('hidden', true); }

  function toast(msg){ const $t = $('<div/>').text(msg).css({ position:'fixed',bottom:'84px',right:'18px',background:'#111827',color:'#fff',padding:'10px 12px',borderRadius:'10px',boxShadow:'0 10px 24px rgba(0,0,0,.28)',zIndex:60,fontSize:'14px' }).hide(); $('body').append($t); $t.fadeIn(120, function(){ setTimeout(function(){ $t.fadeOut(180, function(){ $t.remove(); }); }, 1200); }); }
  function scrollToContact(){ const $target = $('#contact'); if($target.length){ window.scrollTo({ top: $target.offset().top - 60, behavior: 'smooth' }); } }

  // ====== Bindings ======
  $(document).on('click', '.add-to-inquiry', function(){ const $card = $(this).closest('.product-card'); const id = $card.data('id'); const name = $card.data('name'); addItem({ id:id, name:name }); });

  $('.cart-toggle, #fabCart, #openCart').on('click', openDrawer);
  $('#closeDrawer, #drawerOverlay').on('click', closeDrawer);
  $('#clearInquiry').on('click', function(){ clearItems(); });
  $('#inquiryList').on('click', '.remove-btn', function(){ removeItem($(this).data('id')); });
  $('#toContact').on('click', function(){ closeDrawer(); scrollToContact(); });
  
  // 複製功能
  $(document).on('click', '.copy-btn', function(){
    const text = $(this).data('copy');
    const type = $(this).data('type');
    
    if (navigator.clipboard && window.isSecureContext) {
      // 使用現代 Clipboard API
      navigator.clipboard.writeText(text).then(() => {
        const originalText = $(this).text();
        $(this).text('已複製！').addClass('copied');
        setTimeout(() => {
          $(this).text(originalText).removeClass('copied');
        }, 2000);
        toast(`${type === 'line' ? 'LINE ID' : '信箱'}已複製到剪貼簿`);
      }).catch(() => {
        fallbackCopyText(text, type);
      });
    } else {
      // 降級方案
      fallbackCopyText(text, type);
    }
  });
  
  function fallbackCopyText(text, type) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
      document.execCommand('copy');
      toast(`${type === 'line' ? 'LINE ID' : '信箱'}已複製到剪貼簿`);
    } catch (err) {
      toast('複製失敗，請手動複製');
    }
    
    document.body.removeChild(textArea);
  }

  // Admin bindings
  $('.admin-toggle').on('click', openAdmin);
  $('#closeAdmin, #adminOverlay').on('click', closeAdmin);
  $('#resetDefaults').on('click', function(){ resetProducts(); });
  $('#addProductBtn').on('click', function(){ 
    if(!isAuthedLocal()){ toast('需管理權限'); return; } 
    // 確保顯示商品編輯表單
    $('#siteConfigForm').hide();
    $('#productForm').show();
    clearForm(); 
    $prodName.focus(); 
    console.log('切換到新增商品表單'); // 除錯用
  });
  $('#reloadProducts').on('click', async function(){ 
    if(!isAuthedLocal()){ toast('需管理權限'); return; } 
    toast('重新載入中...');
    await renderProducts();
    await renderAdminList();
    toast('商品已重新載入');
  });
  
  // 站台設定編輯
  $('#editSiteConfig').on('click', async function(){
    console.log('點擊編輯站台設定按鈕');
    if(!isAuthedLocal()){ toast('需管理權限'); return; }
    console.log('開始載入站台設定...');
    await loadSiteConfig();
    console.log('顯示站台設定表單');
    $('#siteConfigForm').show();
    $('#productForm').hide();
    console.log('站台設定表單已顯示，商品表單已隱藏');
  });
  
  $('#closeSiteConfig').on('click', function(){
    $('#siteConfigForm').hide();
    $('#productForm').show();
    console.log('關閉站台設定，顯示商品表單');
  });
  
  $('#saveSiteConfig').on('click', async function(){
    if(!isAuthedLocal()){ toast('需管理權限'); return; }
    await saveSiteConfig();
  });
  
  $('#resetSiteConfig').on('click', async function(){
    if(!isAuthedLocal()){ toast('需管理權限'); return; }
    if(confirm('確定要重置為預設設定嗎？')) {
      await resetSiteConfig();
    }
  });
  $adminList.on('click', '.btn-danger', function(){ deleteProduct($(this).data('id')); });
  $adminList.on('click', '.btn-outline', async function(){ 
    const id = $(this).data('id'); 
    const products = await getProducts(); 
    const p = products.find(x => x.id === id); 
    if(p){ 
      // 確保顯示商品編輯表單
      $('#siteConfigForm').hide();
      $('#productForm').show();
      fillForm(p); 
      console.log('切換到商品編輯表單'); // 除錯用
    } 
  });

  $productForm.on('submit', function(e){
    e.preventDefault();
    if(!isAuthedLocal()){ toast('需管理權限'); return; }
    const idRaw = $editingId.val().trim();
    const name = $prodName.val().trim();
    if(!name){ toast('請輸入商品名稱'); return; }
    const desc = $prodDesc.val().trim();
    let img = ($prodImg.val().trim() || '');
    // 允許相對路徑（/uploads/...），如非 http(s) 開頭則視為相對 URL，直接使用
    // 不再強制驗證 URL 格式
    const id = idRaw || slugify(name);
    upsertProduct({ id, name, desc, img });
    clearForm();
  });
  $('#cancelEdit').on('click', function(){ clearForm(); });

  // 站台設定相關函數
  async function loadSiteConfig(){
    try{
      const res = await fetch('./site-config.php', { 
        method: 'GET',
        headers: { 'Cache-Control': 'no-cache' }
      });
      if(!res.ok){ throw new Error('LOAD_FAILED'); }
      const json = await res.json();
      if(json && json.ok && json.config){
        const cfg = json.config;
        $('#siteTitle').val(cfg.site?.title || '');
        $('#brandText').val(cfg.brand?.text || '');
        $('#brandMark').val(cfg.brand?.mark || '');
        $('#lineId').val(cfg.contact?.lineId || '');
        $('#email').val(cfg.contact?.email || '');
        // 將完整設定放入 JSON 編輯器
        try{ $('#siteConfigJson').val(JSON.stringify(cfg, null, 2)); }catch(_){ $('#siteConfigJson').val(''); }
        toast('設定已載入');
      }else{
        throw new Error('INVALID_RESPONSE');
      }
    }catch(e){
      console.error('載入站台設定失敗:', e);
      toast('載入設定失敗');
    }
  }
  
  async function saveSiteConfig(){
    try{
      // 若使用者於 JSON 區塊輸入內容，優先使用
      const raw = ($('#siteConfigJson').val() || '').trim();
      let payload;
      if(raw){
        try{
          payload = JSON.parse(raw);
        }catch(e){
          toast('JSON 格式錯誤，請檢查後再試');
          return;
        }
      } else {
        // 以表單欄位組成
        payload = {
          site: {
            title: $('#siteTitle').val().trim(),
            description: '美國保健品代購｜正品保證・快速送達台灣。維他命C、魚油、膠原蛋白、益生菌等。',
            keywords: '美國保健品代購,正品保證,快速送達台灣,維他命C,魚油,膠原蛋白,益生菌',
            url: '',
            ogImage: ''
          },
          brand: {
            text: $('#brandText').val().trim(),
            mark: $('#brandMark').val().trim()
          },
          contact: {
            lineId: $('#lineId').val().trim(),
            email: $('#email').val().trim()
          }
        };
        if(!payload.site.title || !payload.brand.text || !payload.brand.mark || !payload.contact.lineId || !payload.contact.email){
          toast('請填寫所有必要欄位');
          return;
        }
      }

      const res = await fetch('./site-config.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      
      if(!res.ok){ throw new Error('SAVE_FAILED'); }
      const json = await res.json();
      if(json && json.ok){
        toast('設定已儲存');
        setTimeout(() => location.reload(), 800);
      }else{
        throw new Error('SAVE_FAILED');
      }
    }catch(e){
      console.error('儲存站台設定失敗:', e);
      toast('儲存設定失敗');
    }
  }
  
  async function resetSiteConfig(){
    try{
      const res = await fetch('./site-config.php', { method: 'PUT' });
      if(!res.ok){ throw new Error('RESET_FAILED'); }
      const json = await res.json();
      if(json && json.ok){
        toast('設定已重置');
        // 重新載入頁面以套用預設設定
        setTimeout(() => location.reload(), 1000);
      }else{
        throw new Error('RESET_FAILED');
      }
    }catch(e){
      console.error('重置站台設定失敗:', e);
      toast('重置設定失敗');
    }
  }

  // 圖片上傳
  $prodFile.on('change', async function(){
    const f = this.files && this.files[0];
    if(!f){ return; }
    if(!isAuthedLocal()){ toast('需管理權限'); this.value=''; return; }
    const fd = new FormData();
    fd.append('file', f);
    try{
      const res = await fetch('./upload.php', { method:'POST', body: fd });
      if(!res.ok){ throw new Error('UPLOAD_FAILED'); }
      const json = await res.json();
      if(json && json.ok && json.url){
        $prodImg.val(json.url);
        const editingId = ($editingId.val() || '').trim();
        if(editingId){
          const items = await getProducts();
          const idx = items.findIndex(x => x.id === editingId);
          if(idx >= 0){ items[idx].img = json.url; await setProducts(items); }
          toast('圖片已上傳並套用');
        }else{
          toast('圖片已上傳');
        }
      }
      else{ toast('上傳失敗'); }
    }catch(e){ toast('上傳發生錯誤'); }
    finally{ this.value=''; }
  });

  function slugify(text){ return text.toString().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-zA-Z0-9]+/g,'-').replace(/(^-|-$)/g,'').toLowerCase(); }

  // Apply config to UI
  function applyConfigToUI(){
    if(cfg.siteTitle){ document.title = cfg.siteTitle; }
    if(cfg.brandText){ $('#brandText, #brandTextFooter').text(cfg.brandText); }
    if(cfg.lineId){
      const lineUrl = `https://line.me/ti/p/${encodeURIComponent(cfg.lineId)}`;
      $('#lineLink, #lineCardLink').attr('href', lineUrl);
      $('#lineIdText').text(cfg.lineId);
    }
    if(cfg.email){
      const mailHref = `mailto:${cfg.email}?subject=${encodeURIComponent('保健品代購詢問')}`;
      $('#emailLink').attr('href', mailHref);
      $('#emailText').text(cfg.email);
    }
  }

  // Footer year - 已移除，改用 PHP 變數
  
  // 產生人機驗證（加總題）
  function genCaptcha(){
    const a = Math.floor(1 + Math.random()*9);
    const b = Math.floor(1 + Math.random()*9);
    $('#captchaQ').text(`${a} + ${b} = ?`);
    $('#captchaA').val(String(a));
    $('#captchaB').val(String(b));
    $('#captchaTs').val(String(Date.now()));
    $('#captchaNonce').val(Math.random().toString(36).slice(2));
  }

  // 初始時產生題目
  genCaptcha();
  
  // 初始化管理介面表單狀態
  function initAdminForms() {
    // 確保初始狀態：商品表單隱藏，站台設定表單隱藏
    $('#productForm').hide();
    $('#siteConfigForm').hide();
    console.log('管理介面表單已初始化');
  }

  // 聯絡表單提交
  $('#contactForm').on('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    // 前端簡單驗證：蜜罐需為空、答題需正確
    if((data.website||'').trim() !== ''){ toast('提交失敗'); return; }
    const a = parseInt(String(data.ca||'0'),10)||0;
    const b = parseInt(String(data.cb||'0'),10)||0;
    const ans = parseInt(String(data.captcha||'0'),10)||0;
    if(a + b !== ans){ toast('人機驗證錯誤'); genCaptcha(); return; }
    
    // 加入詢問清單
    const items = getItems();
    if(items.length > 0){
      data.inquiry = JSON.stringify(items);
    }
    
    try {
      const res = await fetch('./contact-submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      
      if(!res.ok){ throw new Error('SUBMIT_FAILED'); }
      const json = await res.json();
      
      if(json && json.ok){
        // 清空表單和詢問清單
        this.reset();
        clearItems();
        
        // 顯示成功訊息
        toast(json.message);
      }else{
        throw new Error('SUBMIT_FAILED');
      }
    }catch(e){
      console.error('提交聯絡表單失敗:', e);
      toast('提交失敗，請稍後再試');
    }
  });
  
  function showContactOptions(options, name) {
    let optionsHtml = '<div class="contact-options-modal">';
    optionsHtml += '<h3>選擇聯絡方式</h3>';
    optionsHtml += '<p>您的詢問已送出，請選擇以下任一方式與我們聯絡：</p>';
    optionsHtml += '<div class="contact-options-grid">';
    
    options.forEach(option => {
      optionsHtml += `
        <div class="contact-option">
          <span class="option-icon">${option.icon}</span>
          <div class="option-content">
            <h4>${option.name}</h4>
            <p>${option.description}</p>
            <a href="${option.action}" target="_blank" rel="noopener" class="btn btn-primary">立即聯絡</a>
          </div>
        </div>
      `;
    });
    
    optionsHtml += '</div></div>';
    
    // 顯示模態框
    $('body').append(optionsHtml);
    setTimeout(() => $('.contact-options-modal').addClass('show'), 100);
    
    // 點擊背景關閉
    $('.contact-options-modal').on('click', function(e){
      if(e.target === this) {
        $(this).remove();
      }
    });
  }

  // Init
  (async function(){ 
    await ensureStatus(); 
    
    // 檢查是否已經有 PHP 渲染的商品
    if ($('.product-card').length === 0) {
      // 沒有商品時才使用 JS 渲染
      await renderProducts(); 
    } else {
      console.log('PHP 已渲染商品，跳過 JS 渲染');
    }
    
    refreshUI(); 
    
    // 初始化管理介面表單狀態
    initAdminForms();
    
    // 保險：初始化結束後再產生一次人機驗證題目
    if($('#captchaQ').length){ genCaptcha(); }
  })();
})(jQuery);
