<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>OTP Verification</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="icon" type="image/png" href="palipa.jpg"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root{--accent:#6f42c1;--card-bg:rgba(255,255,255,0.92)}
    body{background:linear-gradient(135deg,#f5f7fb 0%,#eef2ff 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
    .otp-card{max-width:420px;width:94%;background:var(--card-bg);border-radius:14px;padding:28px;box-shadow:0 8px 30px rgba(22,27,39,0.08)}
    .brand{display:flex;align-items:center;gap:12px;margin-bottom:6px}
    .brand img{width:46px;height:46px;object-fit:cover;border-radius:8px}
    .brand h2{font-size:18px;margin:0;font-weight:600;color:#111}
    .lead{color:#5a5f6b;font-size:14px}
    .otp-input{width:56px;height:56px;border-radius:10px;border:1px solid #e6e9ef;text-align:center;font-size:20px;font-weight:600}
    .otp-row{gap:10px;justify-content:center}
    .muted-link{font-size:13px;color:#6b7280}
    .btn-primary{background:var(--accent);border-color:var(--accent)}
    @media(max-width:420px){.otp-input{width:48px;height:48px}}
  </style>
</head>
<body>

<div class="otp-card">
  <div class="brand">
    <img src="logo.jpg" alt="Logo"/>
    <div>
      <h2>Andrea Mystery Shop</h2>
      <div class="lead"><?php
            if (isset($_SESSION['reset_email'])) {
                echo 'Enter the one-time code we sent to your email to reset your password';
            } else {
                echo 'Enter the one-time code we sent to your email';
            }
        ?></div>
    </div>
  </div>

  <?php if(isset($_GET['error'])): ?>
    <script>Swal.fire({icon:'error', title:'Error', text: <?= json_encode(htmlspecialchars($_GET['error'])) ?>});</script>
  <?php endif; ?>

  <?php if(isset($_SESSION['otp_debug'])): ?>
    <script>Swal.fire({icon:'info', title:'Info', text: <?= json_encode(htmlspecialchars($_SESSION['otp_debug'])) ?>});</script>
    <?php unset($_SESSION['otp_debug']); ?>
  <?php endif; ?>

  <form id="otpForm" method="POST" action="verify_otp_process.php" class="mt-3">
    <div class="d-flex mb-3 w-100 otp-row">
      <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="form-control otp-input" id="o1" />
      <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="form-control otp-input" id="o2" />
      <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="form-control otp-input" id="o3" />
      <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="form-control otp-input" id="o4" />
      <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="form-control otp-input" id="o5" />
      <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="form-control otp-input" id="o6" />
    </div>

    <input type="hidden" name="otp" id="otpHidden" />

    <button id="verifyBtn" type="submit" class="btn btn-primary w-100 mb-2">Verify Code</button>

    <div class="d-flex justify-content-between align-items-center">
      <a class="muted-link" href="LogIn.php">Back to Login</a>
      <a class="muted-link" href="#" id="resendLink">Resend code</a>
    </div>
  </form>
</div>

<script>
  const inputs = Array.from(document.querySelectorAll('.otp-input'));
  inputs.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
      const v = e.target.value.replace(/[^0-9]/g, '');
      e.target.value = v;
      if(v && idx < inputs.length - 1) inputs[idx+1].focus();
      collectToHidden();
    });
    input.addEventListener('keydown', (e) => {
      if(e.key === 'Backspace' && !e.target.value && idx > 0) {
        inputs[idx-1].focus();
      }
    });
    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData('text');
      const chars = paste.replace(/[^0-9]/g,'').slice(0, inputs.length).split('');
      chars.forEach((ch,i) => inputs[i].value = ch);
      const next = Math.min(chars.length, inputs.length-1);
      inputs[next].focus();
      collectToHidden();
    });
  });

  function collectToHidden(){
    const val = inputs.map(i => i.value || '').join('');
    document.getElementById('otpHidden').value = val;
  }

  document.getElementById('otpForm').addEventListener('submit', (e) => {
    collectToHidden();
    const v = document.getElementById('otpHidden').value;
    if(v.length < 6){
      e.preventDefault();
      Swal.fire({icon:'warning', title:'Incomplete', text:'Please enter the 6-digit code.'});
    } else {
      const btn = document.getElementById('verifyBtn');
      btn.disabled = true; btn.innerText = 'Verifying...';
    }
  });

  // autofocus first input on load
  window.addEventListener('load', () => { setTimeout(()=> inputs[0].focus(), 100); });

  // Resend handler (AJAX)
  document.getElementById('resendLink').addEventListener('click', function(e){
    e.preventDefault();
    Swal.fire({title: 'Resending...', didOpen: () => {Swal.showLoading();}});
    fetch('resend_otp.php', {method: 'POST', credentials: 'same-origin'})
      .then(r => r.json())
      .then(data => {
        if(data.status === 'ok'){
          Swal.fire({icon:'success', title:'Sent', text: data.message || 'OTP resent.'});
        } else {
          Swal.fire({icon:'error', title:'Failed', text: data.message || 'Could not resend OTP.'});
        }
      }).catch(err => {
        console.error(err);
        Swal.fire({icon:'error', title:'Error', text: 'Network error while resending OTP.'});
      });
  });
</script>

</body>
</html>
