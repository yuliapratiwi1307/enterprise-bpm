<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Enterprise BPM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login {
            background: white;
            padding: 35px;
            border-radius: 12px;
            width: 340px;
            box-shadow: 0 5px 25px #0001;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            box-sizing: border-box;
        }
        button {
            background: #153e75;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<div class="login">
    <h2>Enterprise BPM</h2>
    <p>Masuk ke sistem</p>

    @if ($errors->any())
        <p style="color:red">Email atau password salah.</p>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
