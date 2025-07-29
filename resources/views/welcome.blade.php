<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Komersial Dan Informasi</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /* Simplified CSS - keeping only essential Tailwind styles */
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            
            /* Default Dark Mode */
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                line-height: 1.5;
                background-color: #0a0a0a;
                color: #EDEDEC;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            
            .container {
                width: 100%;
                max-width: 30rem; /* 4xl */
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .header {
                width: 100%;
                text-align: right;
            }
            
            .nav {
                display: flex;
                gap: 1rem;
                justify-content: flex-end;
                align-items: center;
            }
            
            .btn {
                display: inline-block;
                padding: 0.375rem 1.25rem;
                border-radius: 0.25rem;
                font-size: 0.875rem;
                text-decoration: none;
                transition: all 0.15s ease;
                border: 1px solid transparent;
            }
            
            .btn-primary {
                background-color: white;
                color: black;
                border: 1px solid black;
            }
            
            .btn-primary:hover {
                background-color: #f0f0f0;
            }
            
            .btn-secondary {
                color: #EDEDEC;
                border-color: #3E3E3A;
            }
            
            .btn-secondary:hover {
                border-color: #62605b;
            }
            
            .btn-ghost {
                color: #EDEDEC;
            }
            
            .btn-ghost:hover {
                border-color: #3E3E3A;
            }
            
            .main-content {
                display: flex;
                flex-direction: column-reverse;
                border-radius: 0.5rem;
                overflow: hidden;
                border: 1px solid black;
            }
            
            .content-section {
                background-color: #161615;
                padding: 2rem;
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                align-items: center;
            }
            
            .logo-image {
                width: 120px;
                height: 120px;
                object-fit: contain;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
            }
            
            .welcome-header {
                text-align: center;
            }
            
            .welcome-title {
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
                color: #bbb;
                margin-bottom: 1.25rem;
            }
            
            .admin-section {
                display: flex;
                justify-content: center;
                align-items: center;
                margin: 1rem 0;
            }
            
            .mode-section {
                text-align: center;
                margin-top: 0.5rem;
            }
            
            .mode-text {
                font-size: 0.875rem;
                color: #fbbf24;
                font-weight: 500;
                cursor: pointer;
                transition: color 0.3s ease;
            }
            
            .mode-text:hover {
                color: #f59e0b;
            }
            
            /* Light mode styles (when toggled) */
            body.light-mode {
                background-color: #FDFDFC;
                color: #1b1b18;
            }
            
            body.light-mode .btn-secondary {
                color: #1b1b18;
                border-color: rgba(25, 20, 1, 0.2);
            }
            
            body.light-mode .btn-secondary:hover {
                border-color: rgba(25, 20, 1, 0.3);
            }
            
            body.light-mode .btn-ghost {
                color: #1b1b18;
            }
            
            body.light-mode .btn-ghost:hover {
                border-color: rgba(25, 20, 1, 0.2);
            }
            
            body.light-mode .content-section {
                background-color: white;
                color: #1b1b18;
            }
            
            body.light-mode .welcome-subtitle {
                color: #666;
            }
            
            /* Responsive design */
            @media (min-width: 64rem) {
                .main-content {
                    flex-direction: row;
                }
                
                .content-section {
                    padding: 3rem;
                }
            }
            
            @media (max-width: 640px) {
                .container {
                    max-width: 335px;
                }
                
                .nav {
                    flex-direction: column;
                    gap: 0.5rem;
                }
                
                .content-section {
                    padding: 1.5rem;
                }
            }
        </style>
    @endif
</head>

<body>
    <div class="container">
       
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="content-section">
                <img src="{{ asset('storage/P3GI.png') }}" alt="P3GI Logo" class="logo-image">
                <div class="welcome-header">
                    <h1 class="welcome-title">
                        Selamat Datang
                    </h1>
                    <p class="welcome-subtitle">
                        Sistem Penjualan Komfor
                    </p>
                </div>
                
                <div class="admin-section">
                    <a href="http://127.0.0.1:8000/admin" 
                       target="_blank" 
                       class="btn btn-primary">
                        Login Admin
                    </a>
                </div>
                
                <div class="mode-section">
                    <p class="mode-text" onclick="toggleMode()">
                        ☀️ Ganti Mode Tampilan
                    </p>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function toggleMode() {
            const body = document.body;
            const modeText = document.querySelector('.mode-text');
            
            // Check current mode
            const isLightMode = body.classList.contains('light-mode');
            
            if (isLightMode) {
                // Switch back to dark mode
                body.classList.remove('light-mode');
                modeText.innerHTML = '☀️ Ganti Mode Tampilan';
            } else {
                // Switch to light mode
                body.classList.add('light-mode');
                modeText.innerHTML = '🌙 Ganti Mode Tampilan';
            }
        }
    </script>
</body>
</html>