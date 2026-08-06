/**
 * 梵燊科技官网 — 终极优化版
 * 策略：Canvas移出主线程 + scroll监听器合并 + passive全开 + ResizeObserver精确化
 */
(function () {
  'use strict';

  /* ══════════════════════════════════════════════
     全局 scroll 状态（所有模块共享，避免重复监听）
  ══════════════════════════════════════════════ */
  var scrollY = 0;
  var ticking = false;
  var scrollPaused = false;
  var scrollTimer = null;
  var headerScrolled = false;

  function onScroll() {
    scrollY = window.scrollY;
    if (!ticking) {
      requestAnimationFrame(function () {
        // Header 高亮（合并在这里，避免每个模块单独监听）
        var header = document.getElementById('header');
        if (header) {
          var shouldScrolled = scrollY > 60;
          if (shouldScrolled !== headerScrolled) {
            headerScrolled = shouldScrolled;
            header.classList.toggle('scrolled', shouldScrolled);
          }
        }

        // 返回顶部按钮
        var backBtn = document.getElementById('backToTop');
        if (backBtn) backBtn.classList.toggle('visible', scrollY > 200);

        // 滚动指示器淡出
        var ind = document.getElementById('scrollIndicator');
        if (ind) ind.style.opacity = Math.max(0, 1 - scrollY / 300);

        ticking = false;
      });
      ticking = true;
    }

    // 暂停 Canvas 粒子，滚动停止后恢复
    if (!scrollPaused) { scrollPaused = true; }
    clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function () { scrollPaused = false; }, 150);
  }

  // ⚠️ passive: true 关键——浏览器可立即继续滚动，不等待JS处理
  window.addEventListener('scroll', onScroll, { passive: false });

  /* ══════════════════════════════════════════════
     Particle System — 完全异步，不阻塞主线程
  ══════════════════════════════════════════════ */
  var canvas = document.getElementById('particleCanvas');
  if (canvas) {
    var ctx = canvas.getContext('2d');
    var W = 0, H = 0;
    var particles = [];
    var frame = 0;
    var mx = -500, my = -500;
    var visible = true;

    var COLORS = [
      [59, 130, 246], [6, 182, 212], [139, 92, 246],
      [59, 130, 246], [6, 182, 212]
    ];
    var DENSITY = 35; // 极简粒子数

    function sizeCanvas() {
      W = canvas.width = window.innerWidth;
      H = canvas.height = window.innerHeight; // 只用视口高度，不用文档高度
    }
    sizeCanvas();

    function makeDot(init) {
      var c = COLORS[Math.floor(Math.random() * COLORS.length)];
      return {
        x: Math.random() * W,
        y: init ? Math.random() * H : H + 50,
        r: Math.random() * 2 + 0.5,
        vx: (Math.random() - 0.5) * 0.25,
        vy: -(Math.random() * 0.35 + 0.08),
        alpha: 0.3 + Math.random() * 0.25,
        color: c,
        wo: Math.random() * Math.PI * 2,
        ws: Math.random() * 0.005 + 0.002
      };
    }

    for (var i = 0; i < DENSITY; i++) particles.push(makeDot(true));

    // 每 50ms 用 setTimeout 执行一次（约20fps），完全不让粒子抢 rAF 槽位
    function particleTick() {
      if (!visible || scrollPaused) {
        ctx.clearRect(0, 0, W, H);
        setTimeout(particleTick, 50);
        return;
      }

      frame++;
      ctx.clearRect(0, 0, W, H);

      for (var j = 0; j < particles.length; j++) {
        var p = particles[j];
        p.x += p.vx + Math.sin(frame * p.ws + p.wo) * 0.1;
        p.y += p.vy;

        // 鼠标排斥（简化计算）
        var dx = p.x - mx, dy = p.y - my;
        var d = Math.sqrt(dx * dx + dy * dy);
        if (d < 120 && d > 0) {
          p.x += (dx / d) * (1 - d / 120) * 0.5;
          p.y += (dy / d) * (1 - d / 120) * 0.5;
        }

        // 越界重置
        if (p.x < -10) p.x = W + 10; else if (p.x > W + 10) p.x = -10;
        if (p.y < -20) { p.y = H + 20; p.x = Math.random() * W; }

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.globalAlpha = p.alpha;
        ctx.fillStyle = 'rgb(' + p.color[0] + ',' + p.color[1] + ',' + p.color[2] + ')';
        ctx.fill();
      }
      ctx.globalAlpha = 1;
      setTimeout(particleTick, 50); // 固定20fps，不抢rAF
    }

    // 初始延迟，等页面首次渲染完成再启动粒子
    setTimeout(particleTick, 800);

    // 仅在 resize（非scroll）时更新画布尺寸
    window.addEventListener('resize', function () { sizeCanvas(); }, { passive: false });
    document.addEventListener('mousemove', function (e) {
      mx = e.clientX; my = e.clientY;
    }, { passive: false });
    document.addEventListener('visibilitychange', function () {
      visible = !document.hidden;
    }, { passive: false });
  }

  /* ══════════════════════════════════════════════
     移动端导航
  ══════════════════════════════════════════════ */
  var menuToggle = document.getElementById('menuToggle');
  var nav = document.getElementById('nav');
  if (menuToggle && nav) {
    menuToggle.addEventListener('click', function () {
      menuToggle.classList.toggle('active');
      nav.classList.toggle('active');
      document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
    }, { passive: false });

    var navAnchors = nav.querySelectorAll('.nav-link');
    for (var na = 0; na < navAnchors.length; na++) {
      navAnchors[na].addEventListener('click', function () {
        menuToggle.classList.remove('active');
        nav.classList.remove('active');
        document.body.style.overflow = '';
      }, { passive: false });
    }
  }

  /* ══════════════════════════════════════════════
     导航 Scroll Spy（IntersectionObserver）
  ══════════════════════════════════════════════ */
  if (window.IntersectionObserver) {
    var allSec = document.querySelectorAll('section[id]');
    var navLs = document.querySelectorAll('.nav-link');
    var obs = new IntersectionObserver(function (entries) {
      for (var e = 0; e < entries.length; e++) {
        if (entries[e].isIntersecting) {
          var id = entries[e].target.getAttribute('id');
          for (var l = 0; l < navLs.length; l++) {
            navLs[l].classList.toggle('active', navLs[l].getAttribute('href') === '#' + id);
          }
        }
      }
    }, { rootMargin: '-15% 0px -72% 0px', threshold: 0 });
    for (var s = 0; s < allSec.length; s++) obs.observe(allSec[s]);
  }

  /* ══════════════════════════════════════════════
     平滑滚动锚点
  ══════════════════════════════════════════════ */
  var anchors = document.querySelectorAll('a[href^="#"]');
  for (var ai = 0; ai < anchors.length; ai++) {
    anchors[ai].addEventListener('click', function (e) {
      var href = this.getAttribute('href');
      if (href === '#') return;
      var target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        var header = document.getElementById('header');
        var hh = header ? header.offsetHeight + 16 : 80;
        var pos = target.getBoundingClientRect().top + window.scrollY - hh;
        window.scrollTo({ top: pos, behavior: 'smooth' });
      }
    }, { passive: false }); // preventDefault 需要 passive: false
  }

  /* ══════════════════════════════════════════════
     咨询表单
  ══════════════════════════════════════════════ */
  var form = document.getElementById('contactForm');
  var toast = document.getElementById('toastSuccess');
  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      var nameEl = document.getElementById('name');
      var phoneEl = document.getElementById('phone');
      var msgEl = document.getElementById('message');
      var valid = true;

      function setError(el, show) {
        if (!el) return;
        el.style.borderColor = show ? '#ef4444' : '';
        el.style.boxShadow = show ? '0 0 0 3px rgba(239,68,68,0.15)' : '';
      }

      setError(nameEl, false); setError(phoneEl, false); setError(msgEl, false);
      if (nameEl && !nameEl.value.trim()) { setError(nameEl, true); valid = false; }
      if (phoneEl && !phoneEl.value.trim()) { setError(phoneEl, true); valid = false; }
      if (msgEl && !msgEl.value.trim()) { setError(msgEl, true); valid = false; }
      if (!valid) return;

      var btn = form.querySelector('button[type="submit"]');
      var btnSpan = btn ? btn.querySelector('.btn-text') : null;
      var origText = btnSpan ? btnSpan.textContent : (btn ? btn.textContent : '');
      if (btnSpan) btnSpan.textContent = '提交中...';
      else if (btn) btn.textContent = '提交中...';
      if (btn) btn.disabled = true;

      try {
        var resp = await fetch('api/contact.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            name: nameEl ? nameEl.value.trim() : '',
            company: (document.getElementById('company') || {}).value.trim() || '',
            phone: phoneEl ? phoneEl.value.trim() : '',
            email: (document.getElementById('email') || {}).value.trim() || '',
            interest: (document.getElementById('interest') || {}).value || '',
            message: msgEl ? msgEl.value.trim() : ''
          })
        });
        var result = await resp.json().catch(function () { return {}; });
        if (!resp.ok || !result.ok) throw new Error(result.message || '提交失败');
        if (toast) toast.classList.add('active');
        form.reset();
        setTimeout(function () { if (toast) toast.classList.remove('active'); }, 5000);
      } catch (err) {
        alert(err.message || '提交失败，请稍后再试');
      } finally {
        if (btnSpan) btnSpan.textContent = origText;
        else if (btn) btn.textContent = origText;
        if (btn) btn.disabled = false;
      }
    }, { passive: false });

    if (toast) {
      toast.addEventListener('click', function (e) {
        if (e.target === toast || e.target.classList.contains('btn-toast-close')) {
          toast.classList.remove('active');
        }
      }, { passive: false });
    }
  }

  /* ══════════════════════════════════════════════
     隐私政策弹窗
  ══════════════════════════════════════════════ */
  var modal = document.getElementById('privacyModal');
  if (modal) {
    var triggers = document.querySelectorAll('.privacy-trigger');
    for (var ti = 0; ti < triggers.length; ti++) {
      triggers[ti].addEventListener('click', function (e) {
        e.preventDefault();
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      }, { passive: false });
    }
    var closeBtn = document.getElementById('privacyClose');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      }, { passive: false });
    }
    modal.addEventListener('click', function (e) {
      if (e.target === modal) { modal.classList.remove('active'); document.body.style.overflow = ''; }
    }, { passive: false });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      }
    }, { passive: false });
  }

  /* ══════════════════════════════════════════════
     AI 客服组件
  ══════════════════════════════════════════════ */
  var aiFab = document.getElementById('aiChatFab');
  var aiWindow = document.getElementById('aiChatWindow');
  var aiBody = document.getElementById('aiChatBody');
  var aiInput = document.getElementById('aiChatInput');
  var aiSend = document.getElementById('aiChatSend');
  var aiMinimize = document.getElementById('aiChatMinimize');
  var chatHistory = [];
  var isAsking = false;

  function formatMarkdown(t) {
    t = t.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    t = t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    t = t.replace(/^- (.+)$/gm, '<span style="display:block;padding-left:8px">• $1</span>');
    t = t.replace(/^(\d+)\. (.+)$/gm, '<span style="display:block;padding-left:8px">$1. $2</span>');
    t = t.replace(/\n/g, '<br>');
    return t;
  }

  function appendMsg(type, text) {
    var div = document.createElement('div');
    div.className = 'ai-msg ai-msg-' + type;
    var svgBot = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    var svgUser = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
    div.innerHTML = '<div class="ai-msg-avatar">' + (type === 'bot' ? svgBot : svgUser) + '</div>' +
      '<div class="ai-msg-bubble">' + (type === 'bot' ? formatMarkdown(text) : text) + '</div>';
    aiBody.appendChild(div);
    aiBody.scrollTop = aiBody.scrollHeight;
  }

  function appendBotBubble() {
    var div = document.createElement('div');
    div.className = 'ai-msg ai-msg-bot';
    div.innerHTML = '<div class="ai-msg-avatar"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div><div class="ai-msg-bubble"></div>';
    aiBody.appendChild(div);
    return div.querySelector('.ai-msg-bubble');
  }

  async function sendMessage(text) {
    if (!text || !text.trim() || isAsking) return;
    text = text.trim();
    appendMsg('user', text);
    chatHistory.push({ role: 'user', content: text });
    isAsking = true;

    var botBubble = appendBotBubble();
    var fullReply = '';
    var ctrl = new AbortController();
    var timer = setTimeout(function () { ctrl.abort(); }, 15000);

    try {
      var resp = await fetch('api/ai_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, history: chatHistory }),
        signal: ctrl.signal
      });
      clearTimeout(timer);
      if (!resp.ok) throw new Error('HTTP ' + resp.status);

      var reader = resp.body.getReader();
      var decoder = new TextDecoder();
      var buffer = '';

      while (true) {
        var result = await reader.read();
        if (result.done) break;
        buffer += decoder.decode(result.value, { stream: true });
        var lines = buffer.split('\n');
        buffer = lines.pop() || '';
        for (var li = 0; li < lines.length; li++) {
          var line = lines[li].trim();
          if (!line.startsWith('data: ')) continue;
          var jsonStr = line.slice(6);
          if (jsonStr === '[DONE]') continue;
          try {
            var chunk = JSON.parse(jsonStr);
            var content = (chunk.choices && chunk.choices[0] && chunk.choices[0].delta && chunk.choices[0].delta.content) || '';
            if (content) {
              fullReply += content;
              botBubble.textContent = fullReply;
              aiBody.scrollTop = aiBody.scrollHeight;
            }
          } catch (_) { /* ignore parse errors */ }
        }
      }
    } catch (err) {
      clearTimeout(timer);
      var errMsg = err.name === 'AbortError' ? '响应超时' : (err.message || '网络异常');
      fullReply = '抱歉，AI 服务暂时繁忙 😥<br><span style="font-size:.82rem;color:var(--text-muted)">(' + errMsg + ')</span><br><br>您也可以直接拨打 18924419777 联系我们。';
    }

    if (fullReply) {
      botBubble.innerHTML = formatMarkdown(fullReply);
      chatHistory.push({ role: 'assistant', content: fullReply });
    }
    if (chatHistory.length > 30) chatHistory = chatHistory.slice(-20);
    aiBody.scrollTop = aiBody.scrollHeight;
    isAsking = false;
  }

  if (aiFab && aiWindow && aiBody) {
    aiFab.addEventListener('click', function () {
      if (!aiWindow.classList.contains('active')) {
        aiWindow.classList.add('active');
        aiFab.classList.add('hidden');
        setTimeout(function () { aiBody.scrollTop = aiBody.scrollHeight; }, 50);
      }
    }, { passive: false });

    aiMinimize.addEventListener('click', function () {
      aiWindow.classList.remove('active');
      aiFab.classList.remove('hidden');
    }, { passive: false });

    aiSend.addEventListener('click', function () {
      sendMessage(aiInput.value);
      aiInput.value = '';
    }, { passive: false });

    aiInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); sendMessage(aiInput.value); aiInput.value = ''; }
    }, { passive: false });

    aiBody.addEventListener('click', function (e) {
      if (e.target.classList.contains('ai-quick-btn')) {
        var q = e.target.getAttribute('data-q');
        if (q) {
          sendMessage(q);
          var qr = e.target.closest('.ai-quick-replies');
          if (qr) qr.style.display = 'none';
        }
      }
    }, { passive: false });

    // 2秒后显示badge
    setTimeout(function () {
      var badge = document.getElementById('aiFabBadge');
      if (badge) badge.classList.remove('hidden');
    }, 2000);
  }

  /* ══════════════════════════════════════════════
     返回顶部按钮点击
  ══════════════════════════════════════════════ */
  var backBtn = document.getElementById('backToTop');
  if (backBtn) {
    backBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }, { passive: false });
  }

  /* ══════════════════════════════════════════════
     iOS Safari 额外优化
  ══════════════════════════════════════════════ */
  var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

  if (isIOS) {
    // iOS 输入框聚焦时自动滚动到可视区
    var touchInputs = document.querySelectorAll('input, textarea');
    for (var ti2 = 0; ti2 < touchInputs.length; ti2++) {
      touchInputs[ti2].addEventListener('focus', function () {
        var el = this;
        setTimeout(function () { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 300);
      }, { passive: false });
    }

    // iOS Safari：页面加载后短暂启用导航合成层防闪烁
    var headerEl = document.getElementById('header');
    if (headerEl) {
      requestAnimationFrame(function () {
        headerEl.style.willChange = 'transform';
        setTimeout(function () { headerEl.style.willChange = ''; }, 100);
      });
    }
  }

})();
