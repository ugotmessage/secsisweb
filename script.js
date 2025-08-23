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

  const DEFAULT_IMAGE = 'https://source.unsplash.com/600x400/?supplement';
  const DEFAULT_PRODUCTS = [
    { id:'vitamin-c', name:'維他命C 1000mg', desc:'高劑量每日補給，增強體力與精神，常備保健首選。', img:'https://source.unsplash.com/600x400/?vitamin' },
    { id:'fish-oil', name:'高濃度魚油', desc:'Omega-3 含量高，支持心血管健康與日常保養。', img:'https://source.unsplash.com/600x400/?fish%20oil,supplements' },
    { id:'collagen', name:'膠原蛋白粉', desc:'美妍養護，添加維生素C 配方，沖泡方便好入口。', img:'https://source.unsplash.com/600x400/?collagen,supplement' },
    { id:'probiotics', name:'益生菌複方', desc:'多菌株高含量，幫助調整體質，維持消化道機能。', img:'https://source.unsplash.com/600x400/?probiotics,supplements' },
    { id:'multi-vitamin', name:'綜合維他命', desc:'全方位補給日常所需營養素，簡單一次到位。', img:'https://source.unsplash.com/600x400/?multivitamin' },
    { id:'vitamin-d', name:'維他命D3 2000 IU', desc:'居家必備好朋友，幫助鈣質吸收與免疫防護。', img:'https://source.unsplash.com/600x400/?vitamin%20D' }
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
  function loadProducts(){ try{ const saved = JSON.parse(localStorage.getItem(PRODUCT_KEY) || 'null'); if(Array.isArray(saved) && saved.length){ return saved; } return DEFAULT_PRODUCTS.slice(); }catch(e){ return DEFAULT_PRODUCTS.slice(); } }
  function saveProducts(items){ localStorage.setItem(PRODUCT_KEY, JSON.stringify(items)); }
  function getProducts(){ return loadProducts(); }
  function setProducts(items){ saveProducts(items); renderProducts(); renderAdminList(); }
  function resetProducts(){ if(!isAuthedLocal()){ toast('需管理權限'); return; } localStorage.removeItem(PRODUCT_KEY); setProducts(DEFAULT_PRODUCTS.slice()); }

  function renderProducts(){ const products = getProducts(); $productGrid.empty(); products.forEach(function(p){ const $card = $('<article/>', { class:'product-card', 'data-id':p.id, 'data-name':p.name }); const $imgWrap = $('<div/>', { class:'product-image-wrap' }); const $img = $('<img/>', { src: p.img || DEFAULT_IMAGE, alt: `${p.name} 示意圖` }); const $info = $('<div/>', { class:'product-info' }); const $h3 = $('<h3/>', { text: p.name }); const $p = $('<p/>', { text: p.desc || '' }); const $btn = $('<button/>', { class:'btn btn-add add-to-inquiry', text:'加入詢問清單' }); $imgWrap.append($img); $info.append($h3,$p,$btn); $card.append($imgWrap,$info); $productGrid.append($card); }); }

  // ====== Admin UI ======
  function openAdmin(){ if(!isAuthedLocal()){ toast('需管理權限'); return; } renderAdminList(); $adminDrawer.addClass('open').attr('aria-hidden','false'); $adminOverlay.removeAttr('hidden'); }
  function closeAdmin(){ $adminDrawer.removeClass('open').attr('aria-hidden','true'); $adminOverlay.attr('hidden', true); clearForm(); }
  function renderAdminList(){ const products = getProducts(); $adminList.empty(); products.forEach(function(p){ const $li = $('<li/>', { class:'product-admin-item' }); const $title = $('<div/>', { class:'title', text: p.name }); const $actions = $('<div/>', { class:'admin-item-actions' }); const $edit = $('<button/>', { class:'btn btn-outline', text:'編輯' }).data('id', p.id); const $del = $('<button/>', { class:'btn btn-danger', text:'刪除' }).data('id', p.id); $actions.append($edit,$del); $li.append($title,$actions); $adminList.append($li); }); }
  function clearForm(){ $editingId.val(''); $prodName.val(''); $prodDesc.val(''); $prodImg.val(''); }
  function fillForm(p){ $editingId.val(p.id); $prodName.val(p.name); $prodDesc.val(p.desc || ''); $prodImg.val(p.img || ''); }
  function upsertProduct(data){ if(!isAuthedLocal()){ toast('需管理權限'); return; } const items = getProducts(); const idx = items.findIndex(x => x.id === data.id); if(idx >= 0){ items[idx] = data; } else { items.push(data); } setProducts(items); toast('已儲存商品'); }
  function deleteProduct(id){ if(!isAuthedLocal()){ toast('需管理權限'); return; } let items = getProducts().filter(x => x.id !== id); setProducts(items); toast('已刪除商品'); }

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

  // Admin bindings
  $('.admin-toggle').on('click', openAdmin);
  $('#closeAdmin, #adminOverlay').on('click', closeAdmin);
  $('#resetDefaults').on('click', function(){ resetProducts(); });
  $('#addProductBtn').on('click', function(){ if(!isAuthedLocal()){ toast('需管理權限'); return; } clearForm(); $prodName.focus(); });
  $adminList.on('click', '.btn-danger', function(){ deleteProduct($(this).data('id')); });
  $adminList.on('click', '.btn-outline', function(){ const id = $(this).data('id'); const p = getProducts().find(x => x.id === id); if(p){ fillForm(p); } });

  $productForm.on('submit', function(e){ e.preventDefault(); if(!isAuthedLocal()){ toast('需管理權限'); return; } const idRaw = $editingId.val().trim(); const name = $prodName.val().trim(); if(!name){ toast('請輸入商品名稱'); return; } const desc = $prodDesc.val().trim(); const img = ($prodImg.val().trim() || DEFAULT_IMAGE); const id = idRaw || slugify(name); upsertProduct({ id, name, desc, img }); clearForm(); });
  $('#cancelEdit').on('click', function(){ clearForm(); });

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

  // Footer year
  (function(){ var y = new Date().getFullYear(); $('#year').text(y); })();

  // Init
  (async function(){ await ensureStatus(); renderProducts(); refreshUI(); })();
})(jQuery);
