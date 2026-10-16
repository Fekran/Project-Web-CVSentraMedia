<?php
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventory CV. Sentra Media</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Outfit & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(59, 130, 246, 0.15), transparent 40%),
                        #0f172a; /* Slate 900 */
        }
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.7); /* Slate 800 with transparency */
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <!-- Flash Notification -->
    <?php display_flash_message(); ?>

    <div class="w-full max-w-md">
        <!-- Logo and Heading -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center mb-4">
                <img src="assets/images/logo-sentra-media.jpg" alt="Logo CV. Sentra Media" class="h-16 object-contain drop-shadow-xl">
            </div>
            <h1 class="text-3xl font-extrabold text-white font-outfit tracking-tight">
                CV. SENTRA MEDIA
            </h1>
            <p class="text-slate-400 mt-2 text-sm">
                Sistem Informasi Pengelolaan Stok Buku &amp; Inventaris
            </p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-2xl p-8 shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i class="fas fa-key text-indigo-400"></i> Masuk Ke Sistem
            </h2>

            <form action="actions/login_action.php" method="POST" class="space-y-6">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">

                <!-- Username Input -->
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-300 mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" name="username" id="username" required autocomplete="username"
                            class="block w-full pl-10 pr-3 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            placeholder="Masukkan username Anda">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" name="password" id="password" required autocomplete="current-password"
                            class="block w-full pl-10 pr-3 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            placeholder="Masukkan password Anda">
                    </div>
                </div>

                <!-- Remember Me & Forgot Password (Aesthetic placeholders) -->
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-0">
                        Ingat Saya
                    </label>
                    <span class="hover:text-indigo-400 cursor-pointer transition-colors" onclick="alert('Silakan hubungi Administrator untuk mereset kata sandi Anda.')">Lupa Password?</span>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.98] transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                    <i class="fas fa-sign-in-alt mr-2"></i> Log In
                </button>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-slate-600 mt-8">
            &copy; 2026 CV. Sentra Media. All rights reserved.
        </p>
    </div>

    <!-- Fade alert script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const alert = document.getElementById('flash-alert');
            if (alert) {
                setTimeout(() => {
                    alert.classList.add('opacity-0', 'translate-y-[-20px]');
                    setTimeout(() => alert.remove(), 300);
                }, 4000);
            }
        });
    </script>
</body>
</html>
