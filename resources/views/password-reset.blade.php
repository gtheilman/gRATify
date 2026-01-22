<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your gRATify password to regain access to the team-based learning assessment platform." />
    <title>Password Reset</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f5; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .card { background:#fff; padding:24px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1); width: 360px; }
        .card h2 { margin-top:0; }
        .field { margin-bottom:12px; display:flex; flex-direction:column; }
        label { font-size:14px; margin-bottom:4px; }
        input { padding:10px; border:1px solid #d4d4d8; border-radius:8px; font-size:15px; }
        button { width:100%; padding:12px; border:none; border-radius:8px; background:#2563eb; color:#fff; font-size:16px; cursor:pointer; }
        .msg { margin-top:12px; font-size:14px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Reset Password</h2>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" value="{{ $email }}" autocomplete="email">
        </div>
        <div class="field">
            <label for="password">New Password</label>
            <input id="password" type="password" autocomplete="new-password">
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" autocomplete="new-password">
        </div>
        <button id="submitBtn">Update Password</button>
        <div class="msg" id="message"></div>
    </div>

    <script>
        const token = @json($token);
        const emailInput = document.getElementById('email');
        const msgEl = document.getElementById('message');
        const btn = document.getElementById('submitBtn');

        const readCookie = (name) => {
            const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
            return match ? decodeURIComponent(match[1]) : '';
        };

        btn.addEventListener('click', async () => {
            msgEl.textContent = '';
            btn.disabled = true;
            try {
                await fetch('/sanctum/csrf-cookie', {
                    credentials: 'same-origin',
                });
                const xsrfToken = readCookie('XSRF-TOKEN');
                const res = await fetch('/api/auth/password/reset', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrfToken,
                    },
                    body: JSON.stringify({
                        token,
                        email: emailInput.value,
                        password: document.getElementById('password').value,
                        password_confirmation: document.getElementById('password_confirmation').value,
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data?.error?.message || data.message || data.status || 'Reset failed');
                msgEl.style.color = 'green';
                msgEl.textContent = 'Password updated. You can now log in.';
            } catch (e) {
                msgEl.style.color = 'red';
                msgEl.textContent = e.message || 'Reset failed';
            } finally {
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
