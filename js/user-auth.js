(function () {
  'use strict';
  var state = { user: null, csrf: '' };
  var modal;

  function api(action, data) {
    return fetch('api/user.php?action=' + encodeURIComponent(action), {
      method: data ? 'POST' : 'GET',
      credentials: 'same-origin',
      headers: data ? { 'Content-Type': 'application/json', 'X-CSRF-Token': state.csrf } : {},
      body: data ? JSON.stringify(data) : null
    }).then(function (r) { return r.json().then(function (d) { if (!r.ok || !d.ok) throw new Error(d.message || '请求失败'); return d; }); });
  }

  function render() {
    document.querySelectorAll('[data-auth-guest]').forEach(function (el) { el.hidden = !!state.user; });
    document.querySelectorAll('[data-auth-user]').forEach(function (el) { el.hidden = !state.user; });
    document.querySelectorAll('[data-user-name]').forEach(function (el) { el.textContent = state.user ? state.user.nickname : ''; });
  }

  function setTab(tab) {
    modal.querySelectorAll('.auth-tab').forEach(function (el) { el.classList.toggle('active', el.dataset.tab === tab); });
    modal.querySelectorAll('.auth-form').forEach(function (el) { el.classList.toggle('active', el.dataset.form === tab); });
  }

  function open(tab) { setTab(tab || 'login'); modal.classList.add('active'); document.body.style.overflow = 'hidden'; }
  function close() { modal.classList.remove('active'); document.body.style.overflow = ''; }

  function buildModal() {
    modal = document.createElement('div');
    modal.className = 'auth-modal';
    modal.innerHTML = '<div class="auth-panel" role="dialog" aria-modal="true" aria-label="用户登录注册"><div class="auth-head"><button class="auth-tab active" data-tab="login">登录</button><button class="auth-tab" data-tab="register">注册</button><button class="auth-close" aria-label="关闭">&times;</button></div>' +
      '<form class="auth-form active" data-form="login"><label>手机号码</label><input type="tel" name="phone" inputmode="numeric" maxlength="11" required><label>密码</label><input type="password" name="password" minlength="8" maxlength="72" required><button class="auth-submit" type="submit">登录</button><div class="auth-error"></div></form>' +
      '<form class="auth-form" data-form="register"><label>昵称</label><input type="text" name="nickname" minlength="2" maxlength="30" required><label>手机号码</label><input type="tel" name="phone" inputmode="numeric" maxlength="11" required><label>密码</label><input type="password" name="password" minlength="8" maxlength="72" required><label>确认密码</label><input type="password" name="confirm_password" minlength="8" maxlength="72" required><label class="auth-consent"><input type="checkbox" name="consent" required><span>我已阅读并同意 <a href="user-agreement.php" target="_blank">《用户协议》</a> 和 <a href="privacy.html" target="_blank">《隐私政策》</a></span></label><button class="auth-submit" type="submit">注册并登录</button><div class="auth-error"></div></form></div>';
    document.body.appendChild(modal);
    modal.querySelectorAll('.auth-tab').forEach(function (el) { el.addEventListener('click', function () { setTab(el.dataset.tab); }); });
    modal.querySelector('.auth-close').addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

    modal.querySelector('[data-form="login"]').addEventListener('submit', function (e) {
      e.preventDefault(); submit(e.currentTarget, 'login', { phone: e.currentTarget.phone.value, password: e.currentTarget.password.value });
    });
    modal.querySelector('[data-form="register"]').addEventListener('submit', function (e) {
      e.preventDefault(); var f=e.currentTarget;
      if(f.password.value!==f.confirm_password.value){f.querySelector('.auth-error').textContent='两次输入的密码不一致';return;}
      submit(f,'register',{nickname:f.nickname.value,phone:f.phone.value,password:f.password.value,consent:f.consent.checked});
    });
  }

  function submit(form, action, data) {
    var button=form.querySelector('.auth-submit'), error=form.querySelector('.auth-error');button.disabled=true;error.textContent='';
    api(action,data).then(function(d){state.user=d.user;state.csrf=d.csrf_token||state.csrf;render();close();var next=new URLSearchParams(location.search).get('redirect')||sessionStorage.getItem('auth_redirect');sessionStorage.removeItem('auth_redirect');if(next&&next.charAt(0)==='/')location.href=next;}).catch(function(e){error.textContent=e.message;}).finally(function(){button.disabled=false;});
  }

  function bind() {
    document.addEventListener('click', function (e) {
      var login=e.target.closest('[data-open-login]'), register=e.target.closest('[data-open-register]'), logout=e.target.closest('[data-user-logout]'), protectedLink=e.target.closest('[data-require-auth]');
      if(login){e.preventDefault();open('login');} if(register){e.preventDefault();open('register');}
      if(logout){e.preventDefault();api('logout',{}).then(function(){state.user=null;render();location.href='/';});}
      if(protectedLink&&!state.user){e.preventDefault();sessionStorage.setItem('auth_redirect',new URL(protectedLink.href,location.href).pathname+new URL(protectedLink.href,location.href).search);open('login');}
    });
  }

  buildModal(); bind();
  api('status').then(function(d){state.user=d.user;state.csrf=d.csrf_token;render();var requested=new URLSearchParams(location.search).get('auth');if(requested&&!state.user)open(requested==='register'?'register':'login');}).catch(function(){});
  window.UserAuth={open:open,getUser:function(){return state.user;}};
})();
